<!-- SIDEBAR START -->
<aside class="{{ !user()->dark_theme ? 'sidebar-' . $appTheme->sidebar_theme : '' }}">
    <!-- MOBILE CLOSE SIDEBAR PANEL START -->
    <div class="mobile-close-sidebar-panel w-100 h-100" onclick="closeMobileMenu()" id="mobile_close_panel"></div>
    <!-- MOBILE CLOSE SIDEBAR PANEL END -->

    <!-- MAIN SIDEBAR START -->
    <div class="main-sidebar" id="mobile_menu_collapse">
        <!-- SIDEBAR BRAND START -->
        <div class="sidebar-brand-box dropdown cursor-pointer {{ user()->dark_theme ? 'bg-dark' : '' }}">
            <div class="dropdown-toggle sidebar-brand d-flex align-items-center justify-content-between w-100"
                role="button" tabindex="0" id="dropdownMenuLink" aria-haspopup="true"
                aria-controls="sidebarBrandDropdown" aria-expanded="false">

                @if (companyOrGlobalSetting()->sidebar_logo_style !== 'full')
                    <!-- SIDEBAR BRAND NAME START -->
                    <div class="sidebar-brand-name">
                        <h1 class="mb-0 f-16 f-w-500 text-white-shade mt-0" data-placement="bottom" data-toggle="tooltip"
                            data-original-title="{{ $appName }}">{{ $appName }}
                            <i class="icon-arrow-down icons pl-2"></i>
                        </h1>
                        <div class="mb-0 position-relative pro-name">
                            <span class="bg-light-green rounded-circle"></span>
                            <p class="f-13 text-lightest mb-0" data-placement="bottom" data-toggle="tooltip"
                                data-original-title="{{ user()->name }}">{{ user()->name }}</p>
                        </div>
                    </div>
                    <!-- SIDEBAR BRAND NAME END -->
                    <!-- SIDEBAR BRAND LOGO START -->
                    <div class="sidebar-brand-logo">
                        <img src="{{ companyOrGlobalSetting()->logo_url }}">
                    </div>
                    <!-- SIDEBAR BRAND LOGO END -->
                @else
                    <!-- SIDEBAR BRAND NAME START -->
                    <div class="sidebar-brand-name">
                        <h1 class="mb-0 f-16 f-w-500 text-white-shade mt-0" data-placement="bottom"
                            data-toggle="tooltip" data-original-title="{{ $appName }}">
                            <img src="{{ companyOrGlobalSetting()->logo_url }}">
                        </h1>
                    </div>
                    <!-- SIDEBAR BRAND NAME END -->
                    <!-- SIDEBAR BRAND LOGO START -->
                    <div class="sidebar-brand-logo text-white-shade f-12">
                        <i class="icon-arrow-down icons pl-2"></i>
                    </div>
                    <!-- SIDEBAR BRAND LOGO END -->
                @endif
            </div>
            <!-- DROPDOWN - INFORMATION -->
            <div class="dropdown-menu dropdown-menu-right sidebar-brand-dropdown ml-3" id="sidebarBrandDropdown"
                aria-labelledby="dropdownMenuLink" tabindex="0">
                <div class="d-flex justify-content-between align-items-center profile-box">
                    <a @if(!in_array('client', user_roles())) href="{{ route('employees.show', user()->id) }}" @endif >
                            <div class="profileInfo d-flex align-items-center mr-1 flex-wrap">
                                <div class="profileImg mr-2">
                                    <img class="h-100" src="{{ $user->image_url }}"
                                        alt="{{ user()->name }}">
                                </div>
                                <div class="ProfileData">
                                    <h3 class="f-15 f-w-500 text-dark" data-placement="bottom" data-toggle="tooltip"
                                        data-original-title="{{ user()->name }}">{{ user()->name }}</h3>
                                    <p class="mb-0 f-12 text-dark-grey">{{ user()->employeeDetail->designation->name ?? '' }}</p>
                                </div>
                        </div>
                    </a>
                    <a href="{{ route('profile-settings.index') }}" data-toggle="tooltip"
                        data-original-title="{{ __('app.menu.profileSettings') }}">
                            <i class="side-icon bi bi-pencil-square"></i>
                    </a>
                </div>

                @if (!in_array('client', user_roles()) && ($sidebarUserPermissions['add_employees'] == 4 || $sidebarUserPermissions['add_employees'] == 1) && in_array('employees', user_modules()))
                    <a class="dropdown-item d-flex justify-content-between align-items-center f-15 text-dark invite-member"
                        href="javascript:;">
                        <span>@lang('app.inviteMember') {{ $companyName }}</span>
                        <i class="side-icon bi bi-person-plus"></i>
                    </a>
                @endif

                <a class="dropdown-item d-flex justify-content-between align-items-center f-15 text-dark"
                    href="javascript:;">
                    <label for="dark-theme-toggle">@lang('app.darkTheme')</label>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="dark-theme-toggle"
                            @if (user()->dark_theme) checked @endif>
                        <label class="custom-control-label f-14" for="dark-theme-toggle"></label>
                    </div>
                </a>
                <a class="dropdown-item d-flex justify-content-between align-items-center f-15 text-dark"
                    href="{{ route('logout') }}" onclick="event.preventDefault();
                document.getElementById('logout-form').submit();">
                    @lang('app.logout')
                    <i class="side-icon bi bi-power"></i>
                </a>
            </div>
        </div>
        <!-- SIDEBAR BRAND END -->

        <!-- SIDEBAR MENU START -->
        <div class="sidebar-menu {{ user()->dark_theme ? 'bg-dark' : '' }}" id="appSideMenuScroll">
            @include('sections.menu')
        </div>
        <!-- SIDEBAR MENU END -->
    </div>
    <!-- MAIN SIDEBAR END -->
    <!-- Sidebar Toggler -->
    <div
        class="text-center d-flex justify-content-between align-items-center position-fixed sidebarTogglerBox {{ user()->dark_theme ? 'bg-dark' : '' }}">
        <button class="border-0 d-lg-block d-none text-lightest font-weight-bold" id="sidebarToggle"></button>

        @php
            $appVersionFile = public_path('version.txt');
            $appVersion = File::exists($appVersionFile) ? trim(File::get($appVersionFile)) : '0.0.0';
        @endphp
        <p class="mb-0 text-dark-grey px-1 py-0 rounded f-10">v{{ $appVersion }}</p>
    </div>
    <!-- Sidebar Toggler -->
</aside>
<!-- SIDEBAR END -->

<script>
    (function initSidebarBrandDropdown() {
        const toggle = document.getElementById('dropdownMenuLink');
        const menu = document.getElementById('sidebarBrandDropdown');

        if (!toggle || !menu) {
            return;
        }

        const brandBox = toggle.closest('.sidebar-brand-box');
        const originalParent = menu.parentNode;
        const originalNextSibling = menu.nextSibling;
        let portalHost = null;

        function positionPortal() {
            if (!portalHost) {
                return;
            }

            const toggleRect = toggle.getBoundingClientRect();
            const availableWidth = Math.max(240, window.innerWidth - 8);
            const menuWidth = Math.min(300, availableWidth);
            const left = Math.max(0, Math.min(toggleRect.left, window.innerWidth - menuWidth - 8));

            portalHost.style.setProperty('top', toggleRect.bottom + 'px', 'important');
            portalHost.style.setProperty('left', left + 'px', 'important');
            portalHost.style.setProperty('width', menuWidth + 'px', 'important');
        }

        function openBrandDropdown() {
            if (portalHost) {
                return;
            }

            portalHost = document.createElement('div');
            portalHost.className = 'sidebar-brand-box sidebar-brand-dropdown-portal-host';
            document.body.appendChild(portalHost);
            portalHost.appendChild(menu);

            brandBox.classList.add('show');
            menu.classList.add('show');
            toggle.setAttribute('aria-expanded', 'true');
            positionPortal();
        }

        function closeBrandDropdown() {
            if (!portalHost) {
                return;
            }

            menu.classList.remove('show');
            brandBox.classList.remove('show');
            toggle.setAttribute('aria-expanded', 'false');

            if (originalNextSibling && originalNextSibling.parentNode === originalParent) {
                originalParent.insertBefore(menu, originalNextSibling);
            } else {
                originalParent.appendChild(menu);
            }

            portalHost.remove();
            portalHost = null;
        }

        function toggleBrandDropdown(event) {
            event.preventDefault();
            event.stopPropagation();

            if (typeof event.stopImmediatePropagation === 'function') {
                event.stopImmediatePropagation();
            }

            if (portalHost) {
                closeBrandDropdown();
            } else {
                openBrandDropdown();
            }
        }

        toggle.addEventListener('click', toggleBrandDropdown);
        toggle.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                toggleBrandDropdown(event);
            } else if (event.key === 'Escape') {
                closeBrandDropdown();
            }
        });

        document.addEventListener('click', function(event) {
            if (portalHost && !menu.contains(event.target) && !toggle.contains(event.target)) {
                closeBrandDropdown();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeBrandDropdown();
                toggle.focus();
            }
        });

        window.addEventListener('resize', positionPortal);
        window.addEventListener('scroll', positionPortal, true);
    })();

    function directAccordionContents(item) {
        return Array.prototype.filter.call(item.children, function(child) {
            return child.classList.contains('accordionItemContent');
        });
    }

    function setAccordionOpen(item, open) {
        item.classList.toggle('openIt', open);
        item.classList.toggle('closeIt', !open);

        directAccordionContents(item).forEach(function(content) {
            content.style.display = open ? 'block' : 'none';
            content.style.height = open ? 'auto' : '0';
            content.style.float = 'none';
            content.style.transform = open ? 'none' : 'scaleY(0)';
            content.style.visibility = open ? 'visible' : 'hidden';
            content.style.opacity = open ? '1' : '0';
        });
    }

    function syncSidebarAccordionState() {
        document.querySelectorAll('#appSideMenuScroll .accordionItem').forEach(function(item) {
            setAccordionOpen(item, item.classList.contains('openIt'));
        });
    }

    function toggleSidebarAccordion(event, element) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
            if (typeof event.stopImmediatePropagation === 'function') {
                event.stopImmediatePropagation();
            }
        }

        document.body.classList.remove('sidebar-toggled');

        const currentItem = element.closest('.accordionItem');
        const wasOpen = currentItem.classList.contains('openIt');

        document.querySelectorAll('#appSideMenuScroll .accordionItem').forEach(function(item) {
            setAccordionOpen(item, false);
        });

        if (!wasOpen) {
            setAccordionOpen(currentItem, true);
        }

        return false;
    }

    document.addEventListener('click', function(event) {
        const heading = event.target.closest('#appSideMenuScroll .accordionItemHeading');

        if (heading) {
            toggleSidebarAccordion(event, heading);
        }
    }, true);

    $(document).ready(function() {
        syncSidebarAccordionState();

        $('.invite-member').click(function() {
            const url = "{{ route('employees.invite_member') }}";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('#dark-theme-toggle').change(function() {
            const darkTheme = ($(this).is(':checked')) ? '1' : '0'

            $.easyAjax({
                type: 'POST',
                url: "{{ route('profile.dark_theme') }}",
                blockUI: true,
                data: {
                    '_token': '{{ csrf_token() }}',
                    'darkTheme': darkTheme
                },
                success: function(response) {
                    if (response.status === 'success') {
                        window.location.reload();
                    }
                }
            });

        });

        // Fallback: force sidebar navigation if any script accidentally blocks default link click.
        $(document).on('click', '#appSideMenuScroll a[href]', function(e) {
            if (e.which !== 1 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
                return;
            }

            const href = ($(this).attr('href') || '').trim();
            const target = ($(this).attr('target') || '').trim().toLowerCase();

            if (!href || href === '#' || href.toLowerCase().startsWith('javascript:')) {
                return;
            }

            e.preventDefault();

            if (target === '_blank') {
                window.open(href, '_blank');
                return;
            }

            window.location.href = href;
        });

        // Fallback accordion behavior (independent of bundled main.js handlers)
        $(document).on('click', '#appSideMenuScroll .accordionItemHeading', function(e) {
            return toggleSidebarAccordion(e, this);
        });

    });
</script>

<style>
    #appSideMenuScroll .accordionItem.closeIt > .accordionItemContent {
        display: none !important;
        height: 0 !important;
        float: none !important;
        transform: none !important;
        width: 100% !important;
        visibility: hidden !important;
        opacity: 0 !important;
    }

    #appSideMenuScroll .accordionItem.openIt > .accordionItemContent {
        display: block !important;
        height: auto !important;
        float: none !important;
        transform: none !important;
        width: 100% !important;
        visibility: visible !important;
        opacity: 1 !important;
    }

    body.sidebar-toggled #appSideMenuScroll .accordionItem.openIt > .accordionItemContent {
        display: block !important;
    }

    /* Keep the account dropdown above the menu and page content. */
    aside .main-sidebar,
    aside .main-sidebar * {
        pointer-events: auto !important;
    }

    aside .main-sidebar {
        z-index: 1050 !important;
        overflow: visible !important;
        isolation: isolate;
    }

    aside .sidebar-brand-box {
        position: relative !important;
        z-index: 3 !important;
        overflow: visible !important;
    }

    aside .sidebar-brand-dropdown {
        z-index: 4 !important;
    }

    aside .sidebar-menu {
        position: relative;
        z-index: 1 !important;
    }

    body > .sidebar-brand-dropdown-portal-host {
        position: fixed !important;
        right: auto !important;
        bottom: auto !important;
        height: 0 !important;
        overflow: visible !important;
        pointer-events: none !important;
        isolation: isolate;
        z-index: 2147483647 !important;
    }

    body > .sidebar-brand-dropdown-portal-host > .sidebar-brand-dropdown {
        display: block !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        right: auto !important;
        width: 100% !important;
        margin: 0 !important;
        transform: none !important;
        pointer-events: auto !important;
        z-index: 1 !important;
    }
</style>
