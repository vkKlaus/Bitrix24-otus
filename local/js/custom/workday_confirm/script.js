/**
 * Модуль подтверждения начала рабочего дня для Битрикс24
 */

(function() {
    'use strict';

    console.log('[WorkdayConfirm] Скрипт загружен');

    class WorkdayConfirm {
        constructor() {
            this.isConfirmed = false;
            this.modal = null;
            this.init();
        }

        init() {
            console.log('[WorkdayConfirm] Инициализация...');
            
            this.bindToAvatar();
            this.observeDOM();
            this.startPeriodicCheck();
        }

        bindToAvatar() {
            const avatarSelectors = [
                '[data-id="bx-avatar-widget"]',
                '.air-user-profile',
                '.app__right-bar .air-user-profile',
                '[data-testid="user-id-1"]'
            ];

            let avatar = null;
            for (let selector of avatarSelectors) {
                avatar = document.querySelector(selector);
                if (avatar) {
                    console.log('[WorkdayConfirm] Аватар найден:', selector);
                    break;
                }
            }

            if (!avatar) {
                console.log('[WorkdayConfirm] Аватар не найден, пробуем позже...');
                setTimeout(() => this.bindToAvatar(), 1000);
                return;
            }

            avatar.addEventListener('click', (e) => {
                console.log('[WorkdayConfirm] Клик по аватару');
                this.tryFindButtonInPopup(15);
            });
        }

        tryFindButtonInPopup(attempts) {
            if (attempts <= 0) return;

            setTimeout(() => {
                const button = this.findButton();
                if (button && !button.dataset.workdayConfirmBound) {
                    console.log('[WorkdayConfirm] Кнопка найдена в popup');
                    this.bindButton(button);
                } else if (!button) {
                    this.tryFindButtonInPopup(attempts - 1);
                }
            }, 300);
        }

        observeDOM() {
            if (!window.MutationObserver) return;

            const observer = new MutationObserver((mutations) => {
                for (let mutation of mutations) {
                    if (mutation.addedNodes.length) {
                        const button = this.findButton();
                        if (button && !button.dataset.workdayConfirmBound) {
                            console.log('[WorkdayConfirm] Кнопка найдена через MutationObserver');
                            this.bindButton(button);
                        }
                    }
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }

        startPeriodicCheck() {
            let checks = 0;
            const interval = setInterval(() => {
                checks++;
                const button = this.findButton();
                
                if (button && !button.dataset.workdayConfirmBound) {
                    console.log('[WorkdayConfirm] Кнопка найдена периодической проверкой');
                    this.bindButton(button);
                }
                
                if (checks >= 60) clearInterval(interval);
            }, 1000);
        }

        findButton() {
            const containerSelectors = [
                '.tm-control-panel__actions-list',
                '.tm-control-panel',
                '.intranet-avatar-widget-item__task-status',
                '#bx-avatar-header-popup'
            ];

            for (let containerSel of containerSelectors) {
                const container = document.querySelector(containerSel);
                if (container) {
                    const btn = container.querySelector('button');
                    if (btn && this.isWorkButton(btn)) {
                        console.log('[WorkdayConfirm] Кнопка найдена в контейнере:', containerSel);
                        return btn;
                    }
                }
            }

            const idSelectors = [
                '#buttonStartDropdownAnchorText',
                '#buttonStartDropdownAnchor',
                '#buttonStartDropdownAnchorDropdown'
            ];
            
            for (let selector of idSelectors) {
                const btn = document.querySelector(selector);
                if (btn && this.isWorkButton(btn)) {
                    console.log('[WorkdayConfirm] Кнопка найдена по ID:', selector);
                    return btn;
                }
            }

            const classSelectors = [
                '.tm-control-panel__action',
                '.ui-btn-split__item button',
                '.intranet-avatar-widget-item__task-status button'
            ];
            
            for (let selector of classSelectors) {
                const buttons = document.querySelectorAll(selector);
                for (let btn of buttons) {
                    if (this.isWorkButton(btn)) {
                        console.log('[WorkdayConfirm] Кнопка найдена по классу:', selector);
                        return btn;
                    }
                }
            }

            const allButtons = document.querySelectorAll('button, .ui-btn, [role="button"]');
            for (let btn of allButtons) {
                if (this.isWorkButton(btn)) {
                    console.log('[WorkdayConfirm] Кнопка найдена перебором');
                    return btn;
                }
            }

            return null;
        }

        isWorkButton(btn) {
            const text = (btn.textContent || btn.innerText || '').toLowerCase().trim();
            const keywords = ['начать рабочий день', 'возобновить', 'начать', 'start', 'resume'];
            
            for (let keyword of keywords) {
                if (text.includes(keyword)) {
                    console.log('[WorkdayConfirm] Текст подходит:', text);
                    return true;
                }
            }
            
            return false;
        }

        bindButton(button) {
            if (button.dataset.workdayConfirmBound) {
                return;
            }

            button.dataset.workdayConfirmBound = 'true';
            console.log('[WorkdayConfirm] Кнопка привязана:', button);

            const handler = (event) => {
                console.log('[WorkdayConfirm] Клик, confirmed:', this.isConfirmed);
                
                if (this.isConfirmed) {
                    return true;
                }

                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
                
                this.showModal(button);
                return false;
            };

            button.addEventListener('click', handler, true);
            button.onclick = handler;
        }

        showModal(targetButton) {
            this.closeModal();

            const buttonText = (targetButton.textContent || '').trim();
            const isResume = buttonText.toLowerCase().includes('возобновить');
            
            const title = isResume ? 'Возобновить рабочий день?' : 'Начать рабочий день?';
            const message = isResume 
                ? 'Вы собираетесь возобновить рабочий день. Продолжится отсчет рабочего времени.'
                : 'Вы собираетесь начать рабочий день. Время начала будет зафиксировано.';
            const confirmText = isResume ? 'Возобновить' : 'Начать рабочий день';

            this.modal = document.createElement('div');
            this.modal.className = 'workday-confirm-overlay';
            
            this.modal.innerHTML = 
                '<div class="workday-confirm-modal">' +
                    '<div class="workday-confirm-header">' +
                        '<div class="workday-confirm-icon">' + (isResume ? '▶️' : '🎯') + '</div>' +
                        '<h3 class="workday-confirm-title">' + title + '</h3>' +
                    '</div>' +
                    '<div class="workday-confirm-body">' +
                        '<p class="workday-confirm-text">' + message + '</p>' +
                    '</div>' +
                    '<div class="workday-confirm-footer">' +
                        '<button class="workday-confirm-btn workday-confirm-btn-primary" id="workday-confirm-yes">' + confirmText + '</button>' +
                        '<button class="workday-confirm-btn workday-confirm-btn-secondary" id="workday-confirm-no">Отмена</button>' +
                    '</div>' +
                '</div>';

            document.body.appendChild(this.modal);
            console.log('[WorkdayConfirm] Модальное окно показано');

            const yesBtn = this.modal.querySelector('#workday-confirm-yes');
            const noBtn = this.modal.querySelector('#workday-confirm-no');

            yesBtn.addEventListener('click', () => {
                console.log('[WorkdayConfirm] Подтверждено');
                this.isConfirmed = true;
                this.closeModal();
                
                setTimeout(() => {
                    const newEvent = new MouseEvent('click', {
                        bubbles: true,
                        cancelable: true,
                        view: window
                    });
                    targetButton.dispatchEvent(newEvent);
                    
                    setTimeout(() => {
                        this.isConfirmed = false;
                    }, 1000);
                }, 100);
            });

            noBtn.addEventListener('click', () => {
                console.log('[WorkdayConfirm] Отменено');
                this.closeModal();
            });

            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) {
                    this.closeModal();
                }
            });

            this.escHandler = (e) => {
                if (e.key === 'Escape') {
                    this.closeModal();
                }
            };
            document.addEventListener('keydown', this.escHandler);
        }

        closeModal() {
            if (this.modal) {
                if (this.escHandler) {
                    document.removeEventListener('keydown', this.escHandler);
                    this.escHandler = null;
                }
                this.modal.remove();
                this.modal = null;
            }
        }
    }

    if (typeof BX !== 'undefined' && BX.ready) {
        BX.ready(() => {
            window.workdayConfirm = new WorkdayConfirm();
        });
    } else {
        window.addEventListener('load', () => {
            window.workdayConfirm = new WorkdayConfirm();
        });
    }

})();