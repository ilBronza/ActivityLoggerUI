@once
<style>
    .al-history { --al-ink: #202b42; --al-muted: #65728a; --al-line: #e2e7ef; --al-accent: #4358be; box-sizing: border-box; max-width: 1240px; padding: 48px 36px; margin: auto; color: var(--al-ink); font: 14px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
    .al-history *, .al-history *::before, .al-history *::after { box-sizing: border-box; }
    .al-history h1, .al-history h2, .al-history h3, .al-history p { margin: 0; color: inherit; font-family: inherit; }
    .al-history a { color: var(--al-accent); text-decoration: none; }
    .al-history a:hover { text-decoration: underline; }
    .al-history :focus-visible { outline: 3px solid #8294ef; outline-offset: 3px; }
    .al-header { display: flex; justify-content: space-between; align-items: center; gap: 20px; }
    .al-history .al-eyebrow { color: var(--al-accent); font-size: 11px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; margin-bottom: 8px; }
    .al-history h1 { font-size: 32px; font-weight: 650; letter-spacing: -.035em; line-height: 1.2; }
    .al-history .al-subtitle { margin-top: 10px; font-size: 16px; overflow-wrap: anywhere; }
    .al-id { color: var(--al-muted); font-size: 12px; margin-left: 5px; }
    .al-readonly { flex-shrink: 0; border: 1px solid var(--al-line); background: #fff; border-radius: 20px; padding: 7px 13px; color: var(--al-muted); font-size: 12px; }
    .al-readonly span { margin-right: 5px; }
    .al-overview { display: flex; align-items: center; gap: 32px; padding: 24px 0; margin: 20px 0 24px; border-bottom: 1px solid var(--al-line); }
    .al-overview > div { display: flex; align-items: baseline; gap: 9px; flex-shrink: 0; }
    .al-overview strong { font-size: 26px; letter-spacing: -.04em; font-weight: 600; }
    .al-overview span, .al-history .al-overview p { color: var(--al-muted); font-size: 12px; }
    .al-history .al-overview p { max-width: 410px; margin-left: auto; }
    .al-filters { display: grid; grid-template-columns: 1.25fr 1fr 1fr 1fr 1fr; gap: 14px; padding: 22px; background: #fff; border: 1px solid var(--al-line); border-radius: 12px; }
    .al-filters label { color: var(--al-muted); font-size: 11px; font-weight: 600; display: flex; flex-direction: column; gap: 7px; }
    .al-filters input, .al-filters select { width: 100%; min-width: 0; min-height: 39px; border-radius: 6px; border: 1px solid #d5dce7; color: var(--al-ink); background: #fff; padding: 8px 9px; font: inherit; font-size: 12px; }
    .al-actions { grid-column: 1 / -1; display: flex; align-items: center; gap: 16px; }
    .al-actions button { border: 0; border-radius: 6px; background: var(--al-accent); color: #fff; padding: 9px 19px; font: inherit; font-size: 12px; font-weight: 600; cursor: pointer; }
    .al-actions button:hover { background: #33459d; }
    .al-actions a { font-size: 12px; }
    .al-list-header { display: flex; justify-content: space-between; align-items: center; margin: 34px 0 18px; gap: 12px; }
    .al-history h2 { font-size: 16px; font-weight: 650; }
    .al-list-header > span { font-size: 11px; color: var(--al-muted); }
    .al-timeline { padding-left: 24px; position: relative; }
    .al-timeline::before { content: ''; position: absolute; left: 5px; top: 22px; bottom: 24px; width: 1px; background: #d8e0ec; }
    .al-event { position: relative; margin-bottom: 14px; }
    .al-dot { position: absolute; width: 11px; height: 11px; background: #b2bdd4; border: 3px solid #f5f7fa; border-radius: 50%; left: -24px; top: 27px; }
    .al-event:first-child .al-dot { background: var(--al-accent); }
    .al-event details { border: 1px solid var(--al-line); border-radius: 10px; background: #fff; overflow: hidden; }
    .al-event summary { cursor: pointer; list-style: none; display: flex; align-items: center; justify-content: space-between; padding: 19px 22px; gap: 18px; }
    .al-event summary::-webkit-details-marker { display: none; }
    .al-event summary:hover { background: #fafbfe; }
    .al-event-main { min-width: 0; }
    .al-event-title { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .al-event time { color: var(--al-muted); font-size: 12px; font-variant-numeric: tabular-nums; }
    .al-badge { background: #eef1f7; color: #52617c; border-radius: 5px; padding: 3px 9px; font-size: 11px; font-weight: 650; overflow-wrap: anywhere; }
    .al-badge-created, .al-badge-restored { background: #e7f5ef; color: #217253; }
    .al-badge-updated { background: #eef0fd; color: #495bb4; }
    .al-badge-deleted { background: #fceceb; color: #a94c50; }
    .al-event-meta { display: block; margin-top: 7px; color: var(--al-muted); font-size: 12px; overflow-wrap: anywhere; }
    .al-event-meta > span[aria-hidden] { margin: 0 5px; }
    .al-event-count { display: flex; align-items: center; gap: 20px; color: var(--al-muted); font-size: 11px; flex-shrink: 0; }
    .al-chevron { font-size: 18px; }
    .al-event details[open] .al-chevron { transform: rotate(180deg); }
    .al-event-body { border-top: 1px solid var(--al-line); padding: 0 22px; }
    .al-table-wrap { overflow-x: auto; }
    .al-history table { width: 100%; table-layout: fixed; border-collapse: collapse; font-size: 12px; margin: 0; }
    .al-history thead th { font-size: 10px; letter-spacing: .06em; text-transform: uppercase; color: var(--al-muted); padding: 13px 12px; text-align: left; font-weight: 600; }
    .al-history th:first-child { width: 27%; padding-left: 0; }
    .al-history tbody th, .al-history tbody td { text-align: left; vertical-align: top; padding: 14px 12px; border-top: 1px solid #edf0f5; overflow-wrap: anywhere; }
    .al-history tbody th { font-weight: 500; }
    .al-history tbody code { display: block; font-size: 10px; color: var(--al-muted); margin-top: 4px; background: none; padding: 0; }
    .al-history pre { white-space: pre-wrap; word-break: break-word; margin: 0; font: inherit; border: 0; padding: 0; background: transparent; color: inherit; }
    .al-before { color: #7c5361; background: #fffbfc; }
    .al-after { color: #246c55; background: #f5fbf8; }
    .al-unrecorded { color: var(--al-muted); font-style: italic; }
    .al-history .al-event-id { color: var(--al-muted); font-size: 10px; padding: 12px 0; text-align: right; }
    .al-history .al-no-changes { padding: 18px 0 4px; color: var(--al-muted); font-size: 12px; }
    .al-empty { text-align: center; padding: 48px 20px; border: 1px dashed #d1d9e7; background: #fff; border-radius: 10px; }
    .al-empty > span { font-size: 32px; color: #9aa8bf; }
    .al-history .al-empty h3 { margin: 10px 0 7px; font-size: 17px; }
    .al-history .al-empty p { color: var(--al-muted); margin-bottom: 15px; }
    .al-pagination { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 14px 0 0 24px; font-size: 12px; }
    .al-pagination > span { color: var(--al-muted); }
    .al-history .al-footnote { font-size: 11px; color: var(--al-muted); margin-top: 28px; }
    .al-history .al-notice { padding: 12px 16px; background: #fff6e5; border: 1px solid #ecd8ad; color: #795b2e; border-radius: 7px; margin-bottom: 18px; font-size: 12px; }
    .al-sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0; }
    @media (max-width: 850px) { .al-history { padding: 28px 20px; } .al-filters { grid-template-columns: repeat(2, minmax(0, 1fr)); } .al-overview { flex-wrap: wrap; gap: 12px 26px; } .al-history .al-overview p { max-width: none; flex-basis: 100%; margin-left: 0; } }
    @media (max-width: 520px) { .al-history { padding: 24px 14px; } .al-header { align-items: flex-start; } .al-history h1 { font-size: 27px; } .al-readonly { font-size: 10px; padding: 5px 9px; } .al-filters { padding: 16px; gap: 12px; } .al-filters label:first-child { grid-column: 1 / -1; } .al-timeline { padding-left: 17px; } .al-dot { left: -17px; } .al-event summary { padding: 16px 12px; align-items: flex-start; } .al-event-count { gap: 8px; font-size: 10px; } .al-event-body { padding: 0 12px; } .al-history table { min-width: 470px; } .al-list-header > span { font-size: 10px; } }
    @media (prefers-reduced-motion: reduce) { .al-history * { scroll-behavior: auto; } }
</style>
@endonce
