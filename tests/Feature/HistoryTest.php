<?php

namespace IlBronza\ActivityLoggerUI\Tests\Feature;

use IlBronza\ActivityLoggerUI\History\HistorySource;
use IlBronza\ActivityLoggerUI\History\SourceResolver;
use IlBronza\ActivityLoggerUI\Tests\Fixtures\Record;
use IlBronza\ActivityLoggerUI\Tests\Fixtures\LoggedRecord;
use IlBronza\ActivityLoggerUI\Tests\Fixtures\Storage;
use IlBronza\ActivityLoggerUI\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class HistoryTest extends TestCase
{
    public function test_model_and_id_route_builds_the_complete_history_automatically(): void
    {
        $record = $this->record();
        $storage = Storage::create(['record_id' => $record->id]);
        $child = $this->record(['parent_id' => $record->id]);
        $this->activity($record, ['attributes' => ['name' => 'Main model change']]);
        $this->activity($storage, ['attributes' => ['stored_city' => 'Extra field change']]);
        $this->activity($child, ['attributes' => ['name' => 'Related model change']]);

        foreach (['record', Record::class] as $model) {
            $url = route('activityloggerui.history', ['model' => $model, 'id' => $record->id]);
            $this->get($url)->assertOk()->assertSee('<html', false)
                ->assertSee('Main model change')->assertSee('Extra field change')
                ->assertSee('Related model change');
        }
    }

    public function test_model_and_id_are_preserved_in_filter_and_pagination_links(): void
    {
        config(['activityloggerui.per_page' => 1]);
        $record = $this->record();
        $storage = Storage::create(['record_id' => $record->id]);
        $this->activity($storage, ['attributes' => ['stored_city' => 'First city']]);
        $this->activity($storage, ['attributes' => ['stored_city' => 'Second city']]);
        $this->activity($record, ['attributes' => ['name' => 'Excluded field']]);
        $url = route('activityloggerui.history', ['model' => 'record', 'id' => $record->id]);

        $response = $this->get($url . '?field=city')->assertOk()->assertSee('Second city')
            ->assertDontSee('First city')->assertDontSee('Excluded field')
            ->assertSee('action="' . $url . '"', false);
        $next = $response->viewData('activities')->nextPageUrl();
        $this->assertStringStartsWith($url . '?', $next);
        $this->get($next)->assertOk()->assertSee('First city')->assertDontSee('Second city');
    }

    public function test_legacy_route_keeps_working_with_its_original_parameters(): void
    {
        $record = $this->record();
        $this->activity($record, ['attributes' => ['name' => 'Legacy history']]);
        $url = route('activities.index', ['classBasename' => 'record', 'primary' => $record->id]);

        $this->get($url)->assertOk()->assertSee('Legacy history');
    }

    public function test_route_renders_before_after_and_escapes_values(): void
    {
        $record = $this->record();
        $this->activity($record, [
            'old' => ['name' => 'Prima'],
            'attributes' => ['name' => '<script>alert(1)</script>'],
        ], ['causer_type' => 'user', 'causer_id' => auth()->id()]);

        $this->get($this->history()->url($record))->assertOk()
            ->assertSee('Storico modifiche')->assertSee('Davide')->assertSee('Prima')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_permission_checks_apply_to_route_and_embedded_api(): void
    {
        $record = $this->record(['visible' => false]);
        $this->get($this->history()->url($record))->assertForbidden();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->history()->data($record);
    }

    public function test_guests_cannot_access_history(): void
    {
        $record = $this->record();
        auth()->logout();
        $this->getJson($this->history()->url($record))->assertUnauthorized();
    }

    public function test_invalid_classes_and_missing_records_return_404(): void
    {
        foreach (['NotAClass', 'stdClass'] as $type) {
            $this->get(route('activityloggerui.history', ['model' => $type, 'id' => '1']))->assertNotFound();
        }
        $this->get(route('activityloggerui.history', ['model' => 'record', 'id' => '999']))->assertNotFound();
        $record = $this->record();
        $this->get(route('activityloggerui.history', ['model' => Record::class, 'id' => $record->id]))->assertOk();
    }

    public function test_extra_fields_use_storage_aliases_and_are_read_only(): void
    {
        $record = $this->record();
        $storage = Storage::create(['record_id' => $record->id]);
        $this->activity($storage, ['old' => ['stored_city' => 'Milano'], 'attributes' => [
            'stored_city' => 'Roma', 'note' => 'Nota nuova', 'private_storage' => 'NEVER SHOW',
            'unmapped' => 'UNMAPPED SECRET', 'storage_secret' => 'STORAGE SECRET',
        ]]);
        DB::connection()->enableQueryLog();
        $result = $this->history()->data($record, ['field' => 'city']);

        $this->assertSame(1, $result['activities']->total());
        $this->assertSame('city', $result['entries'][0]['changes'][0]['field']);
        $this->assertSame('Milano', $result['entries'][0]['changes'][0]['old']);
        $this->assertSame('Roma', $result['entries'][0]['changes'][0]['new']);
        $all = $this->history()->data($record);
        $this->assertSame(1, $all['activities']->total());
        $this->assertSame(['note', 'city'], array_column($all['entries'][0]['changes'], 'field'));
        $html = $this->history()->view($record)->render();
        $this->assertStringNotContainsString('UNMAPPED SECRET', $html);
        $this->assertStringNotContainsString('NEVER SHOW', $html);
        $this->assertStringNotContainsString('STORAGE SECRET', $html);
        foreach (DB::connection()->getQueryLog() as $query) {
            $this->assertMatchesRegularExpression('/^select/i', $query['query']);
        }
        $this->assertSame(1, Storage::count());
    }

    public function test_missing_extra_field_storage_is_not_created(): void
    {
        $record = $this->record();
        $this->get($this->history()->url($record))->assertOk()->assertSee('Nessun evento da mostrare');
        $this->assertSame(0, Storage::count());
    }

    public function test_json_and_date_extra_field_casts_are_understood(): void
    {
        $record = $this->record();
        $storage = Storage::create(['record_id' => $record->id]);
        $this->activity($storage, ['attributes' => ['settings' => ['color' => 'blue'], 'due_date' => '2026-10-20']]);
        $result = $this->history()->data($record, ['source' => 'extra:extraFields']);
        $this->assertSame(['settings', 'due_date'], array_column($result['entries'][0]['changes'], 'field'));
        $this->assertStringContainsString('"color": "blue"', $result['entries'][0]['changes'][0]['new']);
    }

    public function test_only_authorized_declared_relations_are_included(): void
    {
        $parent = $this->record();
        $child = $this->record(['name' => 'Visible child', 'parent_id' => $parent->id]);
        $denied = $this->record(['name' => 'Denied child', 'parent_id' => $parent->id, 'visible' => false]);
        $unrelated = $this->record();
        $allowedLog = $this->activity($child, ['attributes' => ['name' => 'Child changed']]);
        $this->activity($denied, ['attributes' => ['name' => 'PRIVATE CHILD']]);
        $this->activity($unrelated, ['attributes' => ['name' => 'UNRELATED']]);

        $result = $this->history()->data($parent);
        $this->assertSame([$allowedLog->id], $result['entries']->pluck('id')->all());
        $this->assertSame('children', $result['entries'][0]['source']);
    }

    public function test_equal_timestamps_have_stable_database_pagination_and_filters(): void
    {
        config(['activityloggerui.per_page' => 2]);
        $record = $this->record();
        $logs = [];
        foreach (range(1, 5) as $index) {
            $logs[] = $this->activity($record, ['attributes' => ['name' => "Value {$index}"]])->id;
        }
        $this->activity($record, [], ['event' => 'created']);
        $filters = ['event' => 'updated', 'source' => 'model', 'from' => '2026-10-16', 'to' => '2026-10-16'];
        $page1 = $this->history()->data($record, $filters);
        $page2 = $this->history()->data($record, $filters + ['page' => 2]);
        $this->assertSame(5, $page1['activities']->total());
        $this->assertSame([$logs[4], $logs[3]], $page1['entries']->pluck('id')->all());
        $this->assertSame([$logs[2], $logs[1]], $page2['entries']->pluck('id')->all());
        $this->assertStringContainsString('event=updated', $page1['activities']->nextPageUrl());
        $this->get($page1['activities']->nextPageUrl())->assertOk()->assertSee('Pagina 2 di 3');
    }

    public function test_null_false_zero_empty_and_missing_values_stay_distinct(): void
    {
        $record = $this->record();
        $this->activity($record, ['old' => ['flag' => false, 'number' => 0, 'text' => '', 'removed' => 'Old only'],
            'attributes' => ['flag' => true, 'number' => null, 'text' => 'Filled', 'new' => 'New only']]);
        $changes = collect($this->history()->data($record)['entries'][0]['changes'])->keyBy('field');
        $this->assertSame('No', $changes['flag']['old']);
        $this->assertSame('0', $changes['number']['old']);
        $this->assertSame('Nullo', $changes['number']['new']);
        $this->assertSame('Testo vuoto', $changes['text']['old']);
        $this->assertFalse($changes['removed']['new_logged']);
        $this->assertFalse($changes['new']['old_logged']);
        $this->assertSame(1, $this->history()->data($record, ['field' => 'number'])['activities']->total());
    }

    public function test_field_filter_matches_exact_keys_in_old_or_attributes(): void
    {
        $record = $this->record();
        $this->activity($record, ['attributes' => ['name_suffix' => 'name']]);
        $this->activity($record, ['old' => ['name' => null]], ['event' => 'deleted']);
        $result = $this->history()->data($record, ['field' => 'name']);
        $this->assertSame(1, $result['activities']->total());
        $this->assertSame('deleted', $result['entries'][0]['event']);
    }

    public function test_excluded_hidden_and_unchanged_fields_are_not_displayed(): void
    {
        $record = $this->record();
        $this->activity($record, ['old' => ['same' => 'unchanged'], 'attributes' => [
            'password' => 'PASSWORD SECRET', 'secret' => 'HIDDEN SECRET', 'updated_at' => 'DATE SECRET', 'same' => 'unchanged',
        ]]);
        $result = $this->history()->data($record);
        $this->assertSame([], $result['entries'][0]['changes']);
        $this->assertSame(0, $this->history()->data($record, ['field' => 'password'])['activities']->total());
    }

    public function test_missing_causers_do_not_fall_back_to_the_log_database(): void
    {
        $record = $this->record();
        $this->activity($record, [], ['causer_type' => 'user', 'causer_id' => 999]);
        $this->activity($record, [], ['causer_type' => 'missing-class', 'causer_id' => 123]);
        $this->get($this->history()->url($record))->assertOk()->assertSee('Autore non disponibile (#999)')
            ->assertSee('Autore non disponibile (#123)');
    }

    public function test_invalid_filters_return_validation_errors(): void
    {
        $record = $this->record();
        foreach ([['source' => 'unknown'], ['from' => 'not-a-date'], ['from' => '2026-10-16', 'to' => '2026-10-01'], ['page' => -1], ['field' => "name->secret"]] as $filters) {
            $this->getJson($this->history()->url($record, $filters))->assertUnprocessable();
        }
    }

    public function test_plain_eloquent_models_require_a_policy(): void
    {
        $record = $this->record();
        $storage = Storage::create(['record_id' => $record->id]);
        $this->get($this->history()->url($storage))->assertForbidden();
        Gate::define('view', fn ($user, $model) => $model instanceof Storage);
        $this->get($this->history()->url($storage))->assertOk();
    }

    public function test_unsupported_casts_are_reported_without_invoking_them(): void
    {
        $record = new class extends Record {
            public function getExtraFieldsCasts(): array
            {
                return ['computed' => 'MissingCalculatedTotalsExtraField', 'parameter' => 'MissingExtraFieldParameter'];
            }
        };
        $resolved = app(SourceResolver::class)->resolve($record);
        $this->assertSame(['computed', 'parameter'], $resolved['unsupported']);
    }

    public function test_custom_sources_respect_authorization_and_aliases(): void
    {
        $record = $this->record();
        $child = $this->record();
        $denied = $this->record(['visible' => false]);
        $model = new class extends Record {
            public array $historySources = [];
            public function getExtraFieldsCasts(): array { return []; }
            public function getActivityHistorySources(): array { return $this->historySources; }
        };
        $model->setRawAttributes($record->getAttributes());
        $model->historySources = [new HistorySource($child, 'custom', 'Archive', ['name' => 'previous_name']), new HistorySource($denied, 'denied')];
        $sources = app(SourceResolver::class)->resolve($model)['sources'];
        $this->assertSame(['model', 'custom'], $sources->pluck('key')->all());
        $this->assertSame(['previous_name'], $sources->last()->publicFields('name'));
    }

    public function test_many_to_many_related_models_are_included_once(): void
    {
        $parent = $this->record();
        $child = $this->record(['parent_id' => $parent->id]);
        $parent->peers()->attach($child);
        $this->activity($child, ['attributes' => ['name' => 'Related change']]);
        $result = $this->history()->data($parent);
        $this->assertSame(1, $result['activities']->total());
        $this->assertSame('children / peers', $result['entries'][0]['source']);
        $this->assertCount(1, $result['entries'][0]['changes']);
        $this->assertSame(1, $this->history()->data($parent, ['source' => 'relation:peers'])['activities']->total());
    }

    public function test_spatie_generated_events_are_read_from_the_configured_model(): void
    {
        config(['activitylog.enabled' => true]);
        Gate::define('view', fn ($user, $model) => $model instanceof LoggedRecord);
        $record = LoggedRecord::create(['name' => 'Original']);
        $record->update(['name' => 'Updated']);

        $result = $this->history()->data($record, ['event' => 'updated']);
        $this->assertSame(1, $result['activities']->total());
        $this->assertSame('Original', $result['entries'][0]['changes'][0]['old']);
        $this->assertSame('Updated', $result['entries'][0]['changes'][0]['new']);
        $this->assertSame('Davide', $result['entries'][0]['causer']);
    }

    public function test_partial_view_and_english_translations(): void
    {
        app()->setLocale('en');
        $html = $this->history()->view($this->record(), [], true)->render();
        $this->assertStringContainsString('Change history', $html);
        $this->assertStringContainsString('No events to display', $html);
        $this->assertStringNotContainsString('<html', $html);
    }

    public function test_sources_can_be_resolved_with_lazy_loading_prevention(): void
    {
        $record = $this->record();
        $record->preventsLazyLoading = true;
        $storage = Storage::create(['record_id' => $record->id]);
        $this->activity($storage, ['attributes' => ['note' => 'Strict loading']]);
        $this->assertSame(1, $this->history()->data($record)['activities']->total());
    }
}
