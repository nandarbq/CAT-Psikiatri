import './bootstrap';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

window.Swal = Swal;

(() => {
    const AJAX_STATE_KEY = '__admin_ajax_pagination';

    const isPrimaryClick = (event) => event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey;

    const isPaginationLink = (link) => {
        if (!(link instanceof HTMLAnchorElement)) return false;
        if (link.target && link.target !== '_self') return false;
        if (link.hasAttribute('download')) return false;

        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) return false;
        if (!url.searchParams.has('page')) return false;

        return link.closest('nav[role="navigation"], .pagination') !== null;
    };

    const executeScripts = (root) => {
        const scripts = Array.from(root.querySelectorAll('script'));
        scripts.forEach((script) => {
            const replacement = document.createElement('script');
            Array.from(script.attributes).forEach((attribute) => {
                replacement.setAttribute(attribute.name, attribute.value);
            });
            replacement.textContent = script.textContent;
            script.replaceWith(replacement);
        });
    };

    const getAdminMain = () => document.querySelector('.admin-content');
    let activeRequest = null;

    const swapAdminMain = async (url, { pushState = true } = {}) => {
        const currentMain = getAdminMain();
        if (!(currentMain instanceof HTMLElement)) {
            window.location.assign(url);
            return;
        }

        if (activeRequest instanceof AbortController) {
            activeRequest.abort();
        }
        const controller = new AbortController();
        activeRequest = controller;

        currentMain.classList.add('is-ajax-loading');
        currentMain.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Admin-Ajax-Pagination': '1',
                },
                signal: controller.signal,
            });

            if (!response.ok) {
                throw new Error('Gagal memuat halaman.');
            }

            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const incomingMain = doc.querySelector('.admin-content');
            if (!(incomingMain instanceof HTMLElement)) {
                window.location.assign(url);
                return;
            }

            const nextMain = incomingMain.cloneNode(true);
            currentMain.replaceWith(nextMain);
            document.title = doc.title || document.title;

            executeScripts(nextMain);
            window.scrollTo({ top: 0, behavior: 'auto' });

            const nextState = {
                ...(window.history.state ?? {}),
                [AJAX_STATE_KEY]: true,
            };
            if (pushState) {
                window.history.pushState(nextState, '', url);
            } else {
                window.history.replaceState(nextState, '', url);
            }
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                return;
            }
            window.location.assign(url);
        } finally {
            const latestMain = getAdminMain();
            if (latestMain instanceof HTMLElement) {
                latestMain.classList.remove('is-ajax-loading');
                latestMain.removeAttribute('aria-busy');
            }

            if (activeRequest === controller) {
                activeRequest = null;
            }
        }
    };

    document.addEventListener('click', (event) => {
        if (!isPrimaryClick(event)) return;
        const target = event.target;
        if (!(target instanceof Element)) return;

        const link = target.closest('a[href]');
        if (!(link instanceof HTMLAnchorElement)) return;
        if (!isPaginationLink(link)) return;

        const adminMain = getAdminMain();
        if (!(adminMain instanceof HTMLElement) || !adminMain.contains(link)) return;

        event.preventDefault();
        swapAdminMain(link.href, { pushState: true });
    });

    window.addEventListener('popstate', () => {
        if (!window.history.state || window.history.state[AJAX_STATE_KEY] !== true) {
            return;
        }

        const adminMain = getAdminMain();
        if (!(adminMain instanceof HTMLElement)) return;
        swapAdminMain(window.location.href, { pushState: false });
    });

    if (getAdminMain() instanceof HTMLElement) {
        const initialState = {
            ...(window.history.state ?? {}),
            [AJAX_STATE_KEY]: true,
        };
        window.history.replaceState(initialState, '', window.location.href);
    }
})();
