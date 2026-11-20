{{-- Small, self-contained layout styles for the custom admin screens.
     The admin panel ships Filament's precompiled CSS (no Node build on the hosting), so arbitrary
     utility classes are not available: these classes only rely on Filament's own color variables. --}}
<style>
    .omni-stack { display: grid; gap: 1.5rem; }
    .omni-layout { display: grid; gap: 1rem; grid-template-columns: minmax(0, 1fr); align-items: start; }
    .omni-two { display: grid; gap: 1.5rem; grid-template-columns: minmax(0, 1fr); align-items: start; }
    @media (min-width: 1024px) {
        .omni-layout { grid-template-columns: minmax(0, 1fr) minmax(0, 2fr); }
        .omni-two { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    .omni-row { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
    .omni-between { display: flex; align-items: center; justify-content: space-between; gap: .5rem; flex-wrap: wrap; }
    .omni-muted { color: var(--gray-500); font-size: .75rem; }
    .omni-text-sm { font-size: .875rem; }
    .omni-strong { font-weight: 600; }
    .omni-danger { color: var(--danger-600); }
    .omni-list { list-style: none; margin: 0; padding: 0; }
    .omni-list > li + li { border-top: 1px solid var(--gray-200); }
    .dark .omni-list > li + li { border-color: rgb(255 255 255 / .1); }
    .omni-pills { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: .75rem; }
    .omni-pill { border: 1px solid var(--gray-300); border-radius: 9999px; padding: .25rem .75rem; font-size: .75rem; background: transparent; cursor: pointer; color: inherit; }
    .omni-pill.is-active { border-color: var(--primary-500); background: var(--primary-50); color: var(--primary-700); }
    .dark .omni-pill { border-color: rgb(255 255 255 / .2); }
    .dark .omni-pill.is-active { background: rgb(255 255 255 / .08); color: var(--primary-400); border-color: var(--primary-500); }
    .omni-conv { display: flex; width: 100%; align-items: center; justify-content: space-between; gap: .5rem; padding: .75rem .5rem; text-align: left; background: transparent; border: 0; cursor: pointer; color: inherit; }
    .omni-conv.is-selected { background: var(--gray-50); }
    .dark .omni-conv.is-selected { background: rgb(255 255 255 / .06); }
    .omni-conv-side { display: flex; flex-direction: column; align-items: flex-end; gap: .25rem; flex-shrink: 0; }
    .omni-ellipsis { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .omni-thread { display: flex; flex-direction: column; gap: .5rem; max-height: 28rem; overflow-y: auto; padding: .75rem; border-radius: .5rem; background: var(--gray-50); }
    .dark .omni-thread { background: rgb(255 255 255 / .05); }
    .omni-msg { display: flex; justify-content: flex-start; }
    .omni-msg.is-out { justify-content: flex-end; }
    .omni-bubble { max-width: 80%; padding: .5rem .75rem; border-radius: .75rem; font-size: .875rem; background: #fff; box-shadow: 0 1px 2px rgb(0 0 0 / .08); }
    .dark .omni-bubble { background: var(--gray-800); }
    .omni-msg.is-out .omni-bubble { background: var(--primary-100); color: var(--primary-950); box-shadow: none; }
    .omni-bubble p { margin: 0; white-space: pre-line; }
    .omni-bubble small { display: block; margin-top: .25rem; font-size: .65rem; color: var(--gray-500); }
    .omni-chips { display: flex; flex-wrap: wrap; gap: .25rem; margin-top: .5rem; }
    .omni-chip { border: 1px solid var(--primary-400); border-radius: 999px; padding: .1rem .6rem; font-size: .75rem; color: var(--primary-700); background: #fff; }
    .dark .omni-chip { background: transparent; color: var(--primary-300); }
    .omni-attachment { display: inline-block; margin-top: .4rem; font-size: .8rem; text-decoration: underline; }
    .omni-form { display: grid; gap: .75rem; margin-top: 1rem; }
    .omni-input { width: 100%; box-sizing: border-box; border: 1px solid var(--gray-300); border-radius: .5rem; padding: .5rem .75rem; font-size: .875rem; background: #fff; color: inherit; }
    .dark .omni-input { background: rgb(255 255 255 / .05); border-color: rgb(255 255 255 / .15); }
    .omni-note { border-radius: .5rem; background: var(--gray-50); padding: .5rem .75rem; font-size: .75rem; }
    .dark .omni-note { background: rgb(255 255 255 / .05); }
    .omni-dl > div + div { margin-top: .6rem; }
    .omni-dl dt { font-weight: 600; font-size: .875rem; }
    .omni-dl dd { margin: 0; font-size: .875rem; }
    .omni-bullets { margin: 0; padding-left: 1.25rem; font-size: .875rem; }
    .omni-source { max-height: 24rem; overflow: auto; white-space: pre-wrap; font-size: .75rem; margin: 0; }
    .omni-images { display: grid; gap: 1rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    @media (min-width: 768px) { .omni-images { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .omni-image { display: block; border: 1px solid var(--gray-200); border-radius: .5rem; padding: .5rem; font-size: .75rem; cursor: pointer; }
    .dark .omni-image { border-color: rgb(255 255 255 / .12); }
    .omni-image.is-selected { border-color: var(--primary-500); box-shadow: 0 0 0 2px var(--primary-500); }
    .omni-thumb { display: block; width: 100%; height: 7rem; object-fit: cover; border-radius: .375rem; margin-bottom: .5rem; }
    .omni-image label, .omni-image-line { display: flex; align-items: center; gap: .5rem; }
    .omni-code { display: block; margin-top: .25rem; padding: .5rem; border-radius: .375rem; background: var(--gray-100); font-size: .75rem; overflow-x: auto; }
    .dark .omni-code { background: rgb(255 255 255 / .08); }
</style>
