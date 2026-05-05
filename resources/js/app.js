import './bootstrap';
import { Fancybox } from '@fancyapps/ui';
import '@fancyapps/ui/dist/fancybox/fancybox.css';

document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const mobileMenu = document.querySelector('[data-mobile-menu]');
    const mobileMenuTrigger = document.querySelector('[data-mobile-menu-trigger]');

    document.querySelectorAll('[data-modal-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const modal = document.getElementById(trigger.dataset.modalOpen);

            if (!modal) {
                return;
            }

            modal.hidden = false;
            body.classList.add('has-modal');
        });
    });

    document.querySelectorAll('[data-modal]').forEach((modal) => {
        if (modal.dataset.open === 'true') {
            modal.hidden = false;
            body.classList.add('has-modal');
        }

        modal.querySelectorAll('[data-modal-close]').forEach((control) => {
            control.addEventListener('click', () => {
                modal.hidden = true;
                body.classList.remove('has-modal');
            });
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        document.querySelectorAll('[data-modal]').forEach((modal) => {
            modal.hidden = true;
        });

        body.classList.remove('has-modal');
    });

    if (mobileMenu && mobileMenuTrigger) {
        mobileMenuTrigger.addEventListener('click', () => {
            const isOpen = !mobileMenu.hidden;

            mobileMenu.hidden = isOpen;
            mobileMenuTrigger.setAttribute('aria-expanded', String(!isOpen));
        });

        mobileMenu.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                mobileMenu.hidden = true;
                mobileMenuTrigger.setAttribute('aria-expanded', 'false');
            });
        });
    }

    document.querySelectorAll('[data-user-menu]').forEach((menu) => {
        const trigger = menu.querySelector('[data-user-menu-trigger]');
        const dropdown = menu.querySelector('[data-user-menu-dropdown]');

        if (!trigger || !dropdown) {
            return;
        }

        trigger.addEventListener('click', () => {
            const isOpen = !dropdown.hidden;

            dropdown.hidden = isOpen;
            trigger.setAttribute('aria-expanded', String(!isOpen));
        });

        document.addEventListener('click', (event) => {
            if (menu.contains(event.target)) {
                return;
            }

            dropdown.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
        });
    });

    Fancybox.bind('[data-fancybox="works"], [data-fancybox="equipment"]', {
        Thumbs: false,
        Toolbar: {
            display: {
                left: [],
                middle: [],
                right: ['close'],
            },
        },
    });
});
