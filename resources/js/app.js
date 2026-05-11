import './bootstrap';
import { Fancybox } from '@fancyapps/ui';
import '@fancyapps/ui/dist/fancybox/fancybox.css';

document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const mobileMenu = document.querySelector('[data-mobile-menu]');
    const mobileMenuTrigger = document.querySelector('[data-mobile-menu-trigger]');

    const showFormFeedback = (form, message, type = 'success') => {
        const feedback = form.closest('[data-cart-card]')?.querySelector('[data-cart-feedback]');

        if (!feedback) {
            return;
        }

        clearTimeout(Number(feedback.dataset.hideTimeout || 0));
        feedback.textContent = message;
        feedback.className = 'rounded-2xl border px-3 py-2 text-xs font-bold shadow-[0_14px_34px_rgba(15,23,42,0.12)]';
        feedback.classList.add(
            type === 'success' ? 'border-emerald-200' : 'border-amber-200',
            type === 'success' ? 'bg-emerald-50' : 'bg-amber-50',
            type === 'success' ? 'text-emerald-700' : 'text-amber-800',
        );

        feedback.dataset.hideTimeout = String(window.setTimeout(() => {
            feedback.classList.add('hidden');
            feedback.textContent = '';
        }, 3000));
    };

    const updateCartCount = (count) => {
        const cartCounters = document.querySelectorAll('[data-cart-count]');

        if (cartCounters.length === 0) {
            return;
        }

        cartCounters.forEach((counter) => {
            const badge = counter.querySelector('span');

            if (!badge) {
                return;
            }

            badge.textContent = count;
            badge.className = 'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-black leading-none ml-2';
            badge.classList.add(
                count > 0 ? 'border-orange-500' : 'border-slate-200',
                count > 0 ? 'bg-orange-500' : 'bg-slate-100',
                count > 0 ? 'text-white' : 'text-slate-600',
            );
        });
    };

    const formatPrice = (amount, className = '') => {
        const normalizedAmount = Number(amount || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

        return `
            <span class="inline-flex flex-wrap items-baseline gap-2 ${className}">
                <span class="text-xl font-black tracking-tight text-slate-950">
                    ${normalizedAmount} <span class="text-sm font-bold text-slate-500">руб.</span>
                </span>
            </span>
        `.trim();
    };

    const formatPhone = (value) => {
        const digits = String(value || '').replace(/\D/g, '');

        if (digits.length === 0) {
            return '';
        }

        const normalized = digits.startsWith('8')
            ? `7${digits.slice(1)}`
            : digits.startsWith('7')
                ? digits
                : `7${digits}`;
        const trimmed = normalized.slice(0, 11);
        const country = trimmed.slice(0, 1);
        const code = trimmed.slice(1, 4);
        const first = trimmed.slice(4, 7);
        const second = trimmed.slice(7, 9);
        const third = trimmed.slice(9, 11);

        let result = `+${country}`;

        if (code) {
            result += ` (${code}`;
        }

        if (code.length === 3) {
            result += ')';
        }

        if (first) {
            result += ` ${first}`;
        }

        if (second) {
            result += `-${second}`;
        }

        if (third) {
            result += `-${third}`;
        }

        return result;
    };

    const updateCartSummary = () => {
        const summaryTotal = document.querySelector('[data-cart-summary-total]');
        const summaryCount = document.querySelector('[data-cart-summary-count]');
        const emptyState = document.querySelector('[data-cart-empty]');
        const checkout = document.querySelector('[data-cart-checkout]');

        if (!summaryTotal && !summaryCount) {
            return;
        }

        const controls = document.querySelectorAll('[data-cart-card] [data-cart-quantity-control]');
        let totalCount = 0;
        let totalAmount = 0;

        controls.forEach((control) => {
            const input = control.querySelector('[data-cart-quantity-input]');
            const lineTotal = control.closest('[data-cart-card]')?.querySelector('[data-cart-line-total]');
            const quantity = Number(input?.value || 0);
            const unitPrice = Number(lineTotal?.dataset.unitPrice || 0);

            totalCount += quantity;
            totalAmount += quantity * unitPrice;
        });

        if (summaryTotal) {
            summaryTotal.innerHTML = formatPrice(totalAmount, 'text-2xl');
        }

        if (summaryCount) {
            summaryCount.textContent = `Позиций в корзине: ${totalCount}.`;
        }

        if (emptyState) {
            emptyState.classList.toggle('hidden', controls.length > 0);
        }

        if (checkout) {
            checkout.className = 'inline-flex w-full items-center justify-center rounded-full px-6 py-3 text-sm font-medium transition';

            if (controls.length > 0) {
                checkout.classList.add('bg-slate-900', 'text-white', 'shadow-[0_12px_28px_rgba(15,23,42,0.18)]', 'hover:bg-orange-700');
                checkout.classList.remove('pointer-events-none', 'border', 'border-slate-300', 'bg-white/80', 'text-slate-400');
            } else {
                checkout.classList.add('pointer-events-none', 'border', 'border-slate-300', 'bg-white/80', 'text-slate-400');
                checkout.classList.remove('bg-slate-900', 'text-white', 'shadow-[0_12px_28px_rgba(15,23,42,0.18)]', 'hover:bg-orange-700');
            }
        }
    };

    document.querySelectorAll('[data-cart-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const submit = form.querySelector('[type="submit"]');
            submit?.setAttribute('disabled', 'disabled');

            try {
                const response = await fetch(form.action, {
                    method: form.method,
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (!response.ok) {
                    const errors = data.errors || {};
                    const message = Object.values(errors).flat()[0] || data.message || 'Не удалось добавить товар.';

                    showFormFeedback(form, message, 'error');

                    return;
                }

                showFormFeedback(form, data.message || 'Товар добавлен в корзину.');
                updateCartCount(Number(data.cart_count || 0));
            } catch (error) {
                showFormFeedback(form, 'Не удалось добавить товар. Попробуйте ещё раз.', 'error');
            } finally {
                submit?.removeAttribute('disabled');
            }
        });
    });

    document.querySelectorAll('[data-cart-quantity-control]').forEach((control) => {
        const input = control.querySelector('[data-cart-quantity-input]');
        const decrease = control.querySelector('[data-cart-quantity-decrease]');
        const increase = control.querySelector('[data-cart-quantity-increase]');
        const productId = control.dataset.productId;
        const stock = Number(control.dataset.stock || 0);
        const isAvailable = control.dataset.available === '1';
        const token = control.dataset.token;

        if (!input || !decrease || !increase || !productId || !token) {
            return;
        }

        const currentQuantity = () => Number(input.value || 0);
        const lineTotal = control.closest('[data-cart-card]')?.querySelector('[data-cart-line-total]');

        const syncControls = (quantity) => {
            input.value = String(quantity);
            decrease.disabled = !isAvailable || quantity <= 1;
            increase.disabled = !isAvailable || (stock > 0 && quantity >= stock);

            if (lineTotal) {
                const unitPrice = Number(lineTotal.dataset.unitPrice || 0);

                lineTotal.innerHTML = formatPrice(unitPrice * quantity);
            }
        };

        const requestCartChange = async (quantity) => {
            const formData = new FormData();
            formData.append('_token', token);

            let url = control.dataset.updateUrl;

            if (currentQuantity() === 0) {
                url = control.dataset.storeUrl;
                formData.append('product_id', productId);
                formData.append('quantity', '1');
            } else {
                formData.append('_method', 'PATCH');
                formData.append('quantity', String(quantity));
            }

            decrease.disabled = true;
            increase.disabled = true;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (!response.ok) {
                    const errors = data.errors || {};
                    const message = Object.values(errors).flat()[0] || data.message || 'Не удалось обновить корзину.';

                    showFormFeedback(control, message, 'error');
                    syncControls(currentQuantity());

                    return;
                }

                const itemQuantity = Number(data.item_quantity || quantity);

                syncControls(itemQuantity);
                updateCartCount(Number(data.cart_count || 0));
                updateCartSummary();
                showFormFeedback(control, data.message || 'Корзина обновлена.');
            } catch (error) {
                showFormFeedback(control, 'Не удалось обновить корзину. Попробуйте ещё раз.', 'error');
                syncControls(currentQuantity());
            }
        };

        decrease.addEventListener('click', () => {
            const nextQuantity = Math.max(1, currentQuantity() - 1);

            if (nextQuantity === currentQuantity()) {
                return;
            }

            requestCartChange(nextQuantity);
        });

        increase.addEventListener('click', () => {
            const nextQuantity = currentQuantity() + 1;

            if (stock > 0 && nextQuantity > stock) {
                return;
            }

            requestCartChange(nextQuantity);
        });

        syncControls(currentQuantity());
    });

    document.querySelectorAll('[data-cart-remove-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const submit = form.querySelector('[type="submit"]');
            const card = form.closest('[data-cart-card]');

            if (!card) {
                return;
            }

            submit?.setAttribute('disabled', 'disabled');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (!response.ok) {
                    const errors = data.errors || {};
                    const message = Object.values(errors).flat()[0] || data.message || 'Не удалось удалить товар.';

                    showFormFeedback(form, message, 'error');

                    return;
                }

                card.remove();
                updateCartCount(Number(data.cart_count || 0));
                updateCartSummary();
            } catch (error) {
                showFormFeedback(form, 'Не удалось удалить товар. Попробуйте ещё раз.', 'error');
            } finally {
                submit?.removeAttribute('disabled');
            }
        });
    });

    updateCartSummary();

    const checkoutSummary = document.querySelector('[data-checkout-summary]');
    const checkoutDelivery = document.querySelector('[data-checkout-delivery]');
    const checkoutDeliveryPrice = document.querySelector('[data-checkout-delivery-price]');
    const checkoutGrandTotal = document.querySelector('[data-checkout-grand-total]');

    if (checkoutSummary && checkoutDelivery && checkoutDeliveryPrice && checkoutGrandTotal) {
        const cartTotal = Number(checkoutSummary.dataset.cartTotal || 0);

        const syncCheckoutTotal = () => {
            const selectedOption = checkoutDelivery.options[checkoutDelivery.selectedIndex];
            const deliveryPrice = Number(selectedOption?.dataset.price || 0);

            checkoutDeliveryPrice.textContent = selectedOption?.dataset.price
                ? `${deliveryPrice.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ')} руб.`
                : 'Выберите в форме';
            checkoutGrandTotal.innerHTML = formatPrice(cartTotal + deliveryPrice);
        };

        checkoutDelivery.addEventListener('change', syncCheckoutTotal);
        syncCheckoutTotal();
    }

    document.querySelectorAll('[data-phone-mask]').forEach((input) => {
        input.addEventListener('input', () => {
            input.value = formatPhone(input.value);
        });

        input.addEventListener('focus', () => {
            if (!input.value) {
                input.value = '+7';
            }
        });

        input.value = formatPhone(input.value);
    });

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
