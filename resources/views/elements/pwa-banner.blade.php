<div
    id="pwa-install-prompt"
    class="pwa-install-prompt alert border shadow-sm rounded d-none mb-0 home-bg-section"
    role="dialog"
    aria-live="polite"
    aria-label="Install app prompt"
>
    <div class="d-flex align-items-center">
        <div class="mr-3">
            <img src="{{ getSetting('site.pwa_icon') ? Storage::url(getSetting('site.pwa_icon')) : asset('img/rounded-logo-gradient.svg') }}"
                 alt="{{ getSetting('site.name') }}"
                 class="pwa-install-prompt__icon rounded">
        </div>

        <div class="flex-grow-1 min-w-0">
            <div class="font-weight-bold text-body mb-1">
                {{__("Install")}} {{ getSetting('site.name') }}
            </div>
            <div class="text-muted small mb-0">
                {{__("Add the app to your home screen for faster access.")}}
            </div>
        </div>

        <div class="ml-3 d-flex align-items-center">
            <button type="button" class="btn btn-sm btn-primary mr-2 mb-0" id="pwa-install-button">
                {{__("Install")}}
            </button>

            <button type="button" class="close pwa-install-prompt__close ml-1" id="pwa-install-dismiss" aria-label="Dismiss">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    </div>
</div>
