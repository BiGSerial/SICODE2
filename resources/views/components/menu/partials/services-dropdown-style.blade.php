<style>
    .services-dropdown-menu {
        overflow: visible !important;
        max-height: none !important;
    }

    .services-dropdown {
        position: relative;
        min-width: 320px;
        overflow: visible;
        --services-dropdown-font-size: 0.95rem;
        --services-dropdown-line-height: 1.25;
    }

    .services-dropdown .menu-item {
        padding: 0.45rem 0.75rem;
        margin: 0.15rem 0.5rem;
        border-radius: 0.35rem;
        background: #ffffff;
        font-weight: 600;
        text-transform: uppercase;
        font-size: var(--services-dropdown-font-size);
        line-height: var(--services-dropdown-line-height);
        letter-spacing: 0.02em;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
    }

    .services-dropdown .menu-link {
        padding: 0.45rem 0.75rem;
        margin: 0.15rem 0.5rem;
        border-radius: 0.35rem;
        background: #ffffff;
        font-weight: 600;
        text-transform: uppercase;
        font-size: var(--services-dropdown-font-size);
        line-height: var(--services-dropdown-line-height);
        letter-spacing: 0.02em;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: inherit;
        text-decoration: none;
    }

    .services-dropdown .menu-link:hover {
        background: #f3f4f6;
        color: inherit;
    }

    .services-dropdown .services-dropdown-direct-item {
        margin: 0.05rem 0.45rem;
        border-radius: 0;
        background: transparent;
        font-weight: 500;
        text-transform: none;
        font-size: var(--services-dropdown-font-size);
        line-height: var(--services-dropdown-line-height);
        letter-spacing: 0;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 0.6rem;
        color: inherit;
        padding: 0.58rem 0.9rem;
        width: calc(100% - 0.9rem);
        box-sizing: border-box;
    }

    .services-dropdown .services-dropdown-direct-item:hover {
        background: #ffffff;
        color: inherit;
    }

    .services-dropdown .menu-single {
        padding: 0.5rem 0.85rem;
        margin: 0.35rem 0.6rem;
        border-radius: 0.5rem;
        background: #ffffff;
        border: 2px solid #d1d5db;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-weight: 700;
        text-transform: uppercase;
        font-size: var(--services-dropdown-font-size);
        line-height: var(--services-dropdown-line-height);
        letter-spacing: 0.04em;
        color: inherit;
        text-decoration: none;
    }

    .services-dropdown .menu-single i {
        font-size: 1rem;
    }

    .services-dropdown .menu-single:hover {
        background: #f3f4f6;
        color: inherit;
    }

    .services-dropdown .menu-item:hover {
        background: #f3f4f6;
    }

    .services-dropdown .menu-item.is-active {
        background: #e5e7eb;
    }

    .services-dropdown .menu-item i {
        transition: transform 0.2s ease;
    }

    .services-dropdown .menu-item.is-active i {
        transform: rotate(90deg);
    }

    .services-dropdown .menu-panel {
        display: none;
        padding: 0.25rem 0.5rem 0.5rem;
    }

    .services-dropdown .menu-panel.is-open {
        display: block;
    }

    .services-dropdown .subheader {
        font-size: var(--services-dropdown-font-size);
        line-height: var(--services-dropdown-line-height);
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6b7280;
        margin: 0.35rem 0.75rem 0.2rem;
    }

    .services-dropdown .submenu {
        position: relative;
        margin-bottom: 0.25rem;
    }

    .services-dropdown .submenu-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 0.35rem 0.75rem;
        border-radius: 0.35rem;
        background: transparent;
        border: 0;
        font-size: var(--services-dropdown-font-size);
        line-height: var(--services-dropdown-line-height);
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .services-dropdown .submenu-toggle:hover {
        background: #f3f4f6;
    }

    .services-dropdown .submenu-toggle.is-active {
        background: #e5e7eb;
    }

    .services-dropdown .submenu-toggle i {
        transition: transform 0.2s ease;
    }

    .services-dropdown .submenu-toggle.is-active i {
        transform: rotate(90deg);
    }

    .services-dropdown .submenu-panel {
        display: none;
        position: absolute;
        top: 52%;
        left: calc(100% - 8px);
        min-width: 260px;
        background: #dbd8d8;
        border-radius: 0.35rem;
        padding: 0.35rem;
        z-index: 1000;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.15);
    }

    .services-dropdown .submenu-panel.is-open {
        display: block;
    }

    .services-dropdown .menu-panel .dropdown-item,
    .services-dropdown .submenu-panel .dropdown-item {
        font-size: var(--services-dropdown-font-size);
        line-height: var(--services-dropdown-line-height);
    }

    .services-dropdown .services-dropdown-header {
        background-color: #ffffff;
        margin: 0 0 0.35rem 0;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.04em;
        font-size: var(--services-dropdown-font-size);
        line-height: var(--services-dropdown-line-height);
        color: #6b7280;
        padding: 0.6rem 0.85rem;
    }

    .services-dropdown .submenu-down .submenu-panel--down {
        position: static;
        min-width: 100%;
        margin-top: 0.3rem;
        margin-left: 0;
        box-shadow: none;
        border: 0;
        background: transparent;
        padding: 0;
        border-radius: 0;
    }

    .services-dropdown .submenu-side .submenu-panel--side {
        position: absolute;
        top: 52%;
        left: calc(100% - 8px);
    }

    .services-dropdown .submenu-side>.submenu-toggle {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        margin-bottom: 0.25rem;
    }

    .services-dropdown .submenu-side>.submenu-toggle.is-active {
        background: #e2e8f0;
    }
</style>
