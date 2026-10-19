@include('activityloggerui::styles')

<section class="al-history" aria-label="{{ __('activityloggerui::history.title') }}">
    <header class="al-header">
        <div>
            <p class="al-eyebrow">{{ __('activityloggerui::history.eyebrow') }}</p>
            <h1>{{ __('activityloggerui::history.title') }}</h1>
            <p class="al-subtitle">{{ $modelName }} <span class="al-id">#{{ $model->getKey() }}</span></p>
        </div>
        <div class="al-readonly"><span aria-hidden="true">◷</span> {{ __('activityloggerui::history.read_only') }}</div>
    </header>

    <div class="al-overview">
        <div><strong>{{ number_format($activities->total(), 0, ',', '.') }}</strong><span>{{ __('activityloggerui::history.results') }}</span></div>
        <div><strong>{{ count($sources) }}</strong><span>{{ __('activityloggerui::history.sources') }}</span></div>
        <p>{{ __('activityloggerui::history.intro') }}</p>
    </div>

    @if(isset($errors) && $errors->any())
        <div class="al-notice" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @if(count($unsupportedFields))
        <p class="al-notice">{{ __('activityloggerui::history.unsupported') }} {{ implode(', ', $unsupportedFields) }}</p>
    @endif

    <form method="get" action="{{ $historyUrl }}" class="al-filters">
        <label>{{ __('activityloggerui::history.source') }}
            <select name="source">
                <option value="">{{ __('activityloggerui::history.all_sources') }}</option>
                @foreach($sources as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['source'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>{{ __('activityloggerui::history.event') }}
            <select name="event">
                <option value="">{{ __('activityloggerui::history.all_events') }}</option>
                @foreach(__('activityloggerui::history.events') as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['event'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
                @if(isset($filters['event']) && !array_key_exists($filters['event'], __('activityloggerui::history.events')))
                    <option value="{{ $filters['event'] }}" selected>{{ $filters['event'] }}</option>
                @endif
            </select>
        </label>
        <label>{{ __('activityloggerui::history.field') }}
            <input name="field" value="{{ $filters['field'] ?? '' }}" placeholder="{{ __('activityloggerui::history.field_hint') }}" maxlength="150">
        </label>
        <label>{{ __('activityloggerui::history.from') }}<input type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
        <label>{{ __('activityloggerui::history.to') }}<input type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
        <div class="al-actions"><button type="submit">{{ __('activityloggerui::history.apply') }}</button><a href="{{ $historyUrl }}">{{ __('activityloggerui::history.reset') }}</a></div>
    </form>

    <div class="al-list-header"><h2>{{ __('activityloggerui::history.timeline') }}</h2><span>{{ __('activityloggerui::history.newest_first') }}</span></div>

    <div class="al-timeline">
        @forelse($entries as $entry)
            <article class="al-event">
                <div class="al-dot" aria-hidden="true"></div>
                <details @if($loop->first || isset($filters['field'])) open @endif>
                    <summary>
                        <span class="al-event-main">
                            <span class="al-event-title"><span class="al-badge al-badge-{{ in_array($entry['event'], ['created', 'updated', 'deleted', 'restored']) ? $entry['event'] : 'custom' }}">{{ $entry['event_label'] }}</span><time datetime="{{ $entry['datetime'] }}">{{ $entry['date'] }}</time></span>
                            <span class="al-event-meta">{{ $entry['causer'] }} <span aria-hidden="true">·</span> {{ $entry['source'] }} <span class="al-id">#{{ $entry['subject_id'] }}</span></span>
                        </span>
                        <span class="al-event-count">{{ trans_choice('activityloggerui::history.change_count', count($entry['changes']), ['count' => count($entry['changes'])]) }} <span class="al-chevron" aria-hidden="true">⌄</span></span>
                    </summary>
                    <div class="al-event-body">
                        @if(count($entry['changes']))
                            <div class="al-table-wrap">
                                <table>
                                    <caption class="al-sr-only">{{ __('activityloggerui::history.event') }} #{{ $entry['id'] }}</caption>
                                    <thead><tr><th scope="col">{{ __('activityloggerui::history.field') }}</th><th scope="col">{{ __('activityloggerui::history.before') }}</th><th scope="col">{{ __('activityloggerui::history.after') }}</th></tr></thead>
                                    <tbody>
                                        @foreach($entry['changes'] as $change)
                                            <tr>
                                                <th scope="row"><span>{{ $change['label'] }}</span><code>{{ $change['field'] }}</code></th>
                                                <td class="al-before @if(!$change['old_logged']) al-unrecorded @endif"><pre>{{ $change['old'] }}</pre></td>
                                                <td class="al-after @if(!$change['new_logged']) al-unrecorded @endif"><pre>{{ $change['new'] }}</pre></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="al-no-changes">{{ __('activityloggerui::history.no_changes') }}</p>
                        @endif
                        <p class="al-event-id">{{ __('activityloggerui::history.event') }} #{{ $entry['id'] }}</p>
                    </div>
                </details>
            </article>
        @empty
            <div class="al-empty"><span aria-hidden="true">◷</span><h3>{{ __('activityloggerui::history.empty') }}</h3><p>{{ __('activityloggerui::history.empty_hint') }}</p>@if(count($filters))<a href="{{ $historyUrl }}">{{ __('activityloggerui::history.reset') }}</a>@endif</div>
        @endforelse
    </div>

    @if($activities->hasPages())
        <nav class="al-pagination" aria-label="{{ __('activityloggerui::history.pagination') }}">
            @if($activities->previousPageUrl())<a href="{{ $activities->previousPageUrl() }}" rel="prev">← {{ __('activityloggerui::history.previous') }}</a>@else<span></span>@endif
            <span>{{ __('activityloggerui::history.page', ['current' => $activities->currentPage(), 'last' => $activities->lastPage()]) }}</span>
            @if($activities->nextPageUrl())<a href="{{ $activities->nextPageUrl() }}" rel="next">{{ __('activityloggerui::history.next') }} →</a>@else<span></span>@endif
        </nav>
    @endif
    <p class="al-footnote">{{ __('activityloggerui::history.recorded_only') }}</p>
</section>
