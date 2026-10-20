define([
    'uiComponent',
    'ko',
    'jquery',
    'mage/storage',
    'mage/url',
    'Magento_Customer/js/customer-data',
    'mage/translate',
    'mage/cookies',
    'swiper',
    'underscore'
], function (Component, ko, $, storage, urlBuilder, customerData, $t, cookies, Swiper, _) {
    'use strict';

    return Component.extend({
        defaults: {
            messages: ko.observableArray([]),
            currentInput: ko.observable(''),
            isLoading: ko.observable(false),
            addingToCart: ko.observable(null),
            chatState: ko.observable('minimized')
        },

        initialize: function () {
            this._super();
            this._sessionLoaded = false;

            this.scrollToBottom = _.debounce(this.scrollToBottom.bind(this), 50);

            this.syncSwiperLayout = _.debounce(() => {
                if (this.chatState() === 'expanded' || this.chatState() === 'minimized') {
                    this.destroyProductsSwipers();
                } else {
                    this.initProductsSwiper();
                }
                this.scrollToBottom();
            }, 360);

            document.addEventListener('load', (e) => {
                if (e.target && e.target.tagName === 'IMG' && $(e.target).closest('#ai-chat-messages-body').length) {
                    this.scrollToBottom();
                }
            }, true);

            this.isMinimized = ko.pureComputed(function () {
                return this.chatState() === 'minimized';
            }, this);

            this.isOpen = ko.pureComputed(function () {
                return this.chatState() !== 'minimized';
            }, this);

            this.isFullscreen = ko.pureComputed(function () {
                return this.chatState() === 'expanded';
            }, this);

            this.chatState.subscribe(function (newState) {
                let storageState = newState === 'expanded' ? 'open' : newState;
                localStorage.setItem('aiAssistantState', storageState);

                document.body.classList.remove('ai-chat-open', 'ai-chat-fullscreen');

                if (newState === 'open') {
                    document.body.classList.add('ai-chat-open');
                } else if (newState === 'expanded') {
                    document.body.classList.add('ai-chat-fullscreen');
                }
            });

            let aiAssistantState = localStorage.getItem('aiAssistantState');

            if (aiAssistantState && aiAssistantState !== 'minimized') {
                this.openChat(aiAssistantState);
            }

            return this;
        },

        addMessage: function (type, text, options = {}) {
            this.messages.push({
                type: type,
                text: text,
                status: options.status || null,
                products: options.products || [],
                groups: options.groups || [],
                comparison: options.comparison || null,
                suggested_replies: ko.observableArray(options.suggested_replies || [])
            });
            this.scrollToBottom();
        },

        formatMessageText: function (text) {
            if (!text || typeof text !== 'string') {
                return '';
            }

            let html = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            html = html.replace(/__(.*?)__/g, '<strong>$1</strong>');
            html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
            html = html.replace(/([^\n])\s+(\d+\.\s+)/g, '$1\n$2');

            return html;
        },

        sendSuggestedReply: function (replyData, messageObj) {
            if (ko.isObservable(messageObj.suggested_replies)) {
                messageObj.suggested_replies([]);
            }
            this.sendSuggestedQuery(replyData.text || replyData.label, null);
        },

        initGallerySwipers: function () {
            _.defer(function () {
                let containers = document.querySelectorAll('#ai-assistant-root .ai-gallery-swiper:not(.swiper-initialized)');
                let baseDelay = 3000;

                containers.forEach(function (el, index) {
                    let slides = el.querySelectorAll('.swiper-slide');
                    if (slides.length <= 1) {
                        return;
                    }

                    el.classList.add('swiper-initialized');

                    new Swiper(el, {
                        slidesPerView: 1,
                        spaceBetween: 0,
                        loop: true,
                        effect: 'fade',
                        fadeEffect: { crossFade: true },
                        speed: 600,
                        autoplay: {
                            delay: baseDelay + (index * 800),
                            disableOnInteraction: false,
                            pauseOnMouseEnter: true
                        },
                        pagination: {
                            el: el.querySelector('.swiper-pagination'),
                            clickable: true
                        },
                        navigation: {
                            nextEl: el.querySelector('.swiper-button-next'),
                            prevEl: el.querySelector('.swiper-button-prev')
                        }
                    });
                });
            }, 150);
        },
        initProductsSwiper: function () {
            let rows = document.querySelectorAll('#ai-assistant-root .ai-products-row:not(.swiper-initialized)');

            rows.forEach(function (row) {
                if (row.children.length <= 1) {
                    return;
                }
                row.classList.add('swiper');
                let wrapper = document.createElement('div');
                wrapper.className = 'swiper-wrapper';

                Array.from(row.children).forEach(function (child) {
                    let slide = document.createElement('div');
                    slide.className = 'swiper-slide';
                    slide.appendChild(child);
                    wrapper.appendChild(slide);
                });

                row.appendChild(wrapper);

                let prevButton = document.createElement('div');
                prevButton.className = 'swiper-button-prev';
                row.appendChild(prevButton);

                let nextButton = document.createElement('div');
                nextButton.className = 'swiper-button-next';
                row.appendChild(nextButton);

                let compareCard = row.parentElement
                    ? row.parentElement.querySelector('.ai-compare-card')
                    : null;

                let swiperInstance = new Swiper(row, {
                    slidesPerView: 1.3,
                    centeredSlides: true,
                    centeredSlidesBounds: true,
                    centerInsufficientSlides: false,
                    loop: false,
                    spaceBetween: 12,
                    grabCursor: true,
                    navigation: {
                        nextEl: nextButton,
                        prevEl: prevButton
                    }
                });

                row.classList.add('swiper-initialized');
                row.swiper = swiperInstance;

                // Sync compare card scroll with product swiper
                if (compareCard) {
                    let compareInner = compareCard.querySelector('.ai-compare-inner');
                    if (compareInner) {
                        swiperInstance.on('setTranslate', function (swiper, translate) {
                            compareInner.style.transform = 'translateX(' + translate + 'px)';
                        });
                        swiperInstance.on('setTransition', function (swiper, duration) {
                            compareInner.style.transitionDuration = duration + 'ms';
                        });
                    }
                }
            });
        },

        destroyProductsSwipers: function () {
            let rows = document.querySelectorAll('#ai-assistant-root .ai-products-row.swiper-initialized');

            rows.forEach(function (row) {
                if (row.swiper) {
                    row.swiper.destroy(true, true);
                }

                let wrapper = row.querySelector('.swiper-wrapper');
                if (wrapper) {
                    Array.from(wrapper.children).forEach(function (slide) {
                        if (slide.firstChild) {
                            row.appendChild(slide.firstChild);
                        }
                    });
                    wrapper.remove();
                }

                row.querySelectorAll(':scope > .swiper-button-next, :scope > .swiper-button-prev').forEach(function (nav) {
                    nav.remove();
                });

                row.classList.remove('swiper', 'swiper-initialized');
            });
        },
        loadSessionChat: function () {
            if (this._sessionLoaded) {
                return $.Deferred().resolve().promise();
            }
            return storage.get(
                urlBuilder.build('ai-assistant/chat/load'),
                'json',
                true
            ).done((response) => {
                if (response.success) {
                    this._sessionLoaded = true;
                    response.messages.forEach((message) => {
                        this.addMessage(message.type || message.role, message.content, message);
                    });
                }
            });
        },

        openChat: function (state = 'open') {
            this.loadSessionChat().then(() => {
                this.syncSwiperLayout();
            });
            this.chatState(state);
        },

        closeChat: function () {
            this.chatState('minimized');
            this.syncSwiperLayout();
        },

        toggleFullscreen: function () {
            let goingFullscreen = this.chatState() !== 'expanded';
            this.chatState(goingFullscreen ? 'expanded' : 'open');
            this.syncSwiperLayout();
        },

        scrollToBottom: function () {
            let container = document.getElementById('ai-chat-messages-body');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },

        handleKeyPress: function (data, event) {
            if (event.keyCode === 13) {
                this.sendMessage();
                return false;
            }
            return true;
        },

        sendSuggestedQuery: function (text, topicKey) {
            this.currentInput(text);
            this.sendMessage(topicKey);
        },

        clearConversation: function () {
            if (this.isLoading()) {
                return;
            }

            storage.post(
                urlBuilder.build('ai-assistant/chat/clear'),
                JSON.stringify({ form_key: $.mage.cookies.get('form_key') || '' }),
                true
            ).done((response) => {
                if (response.success) {
                    this._sessionLoaded = false;
                    this.messages.removeAll();
                    this.currentInput('');
                    this.scrollToBottom();
                }
            });
        },

        buildUenc: function (url) {
            let encoded;
            try {
                encoded = window.btoa(unescape(encodeURIComponent(url)));
            } catch (e) {
                encoded = window.btoa(url);
            }
            return encoded.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '~');
        },

        /**
         * Push a status message (success/error) into the chat.
         *
         * @param {String} status - 'success' or 'error'
         * @param {String} text
         */
        pushStatusMessage: function (status, text) {
            this.addMessage('status', text, { status: status });
        },

        addToCart: function (product) {
            let productId = product && parseInt(product.product_id, 10);

            if (!productId || this.addingToCart()) {
                return false;
            }

            if (product.is_configurable || product.has_options) {
                if (product.url) {
                    window.location.href = product.url;
                    return true;
                }
            }

            this.addingToCart(String(productId));

            let uenc = this.buildUenc(window.location.href);
            let addToCartUrl = urlBuilder.build(
                'checkout/cart/add/uenc/' + encodeURIComponent(uenc) + '/product/' + productId + '/'
            );
            let formKey = $.mage.cookies.get('form_key') || '';
            let productName = product.name || '';

            $('[data-block="minicart"]').trigger('contentLoading');

            $.ajax({
                url: addToCartUrl,
                type: 'POST',
                data: { product: productId, qty: 1, form_key: formKey },
                dataType: 'json',
                showLoader: true
            }).done((response) => {
                customerData.reload(['cart'], true);
                $('body').trigger('contentUpdated');
                $(document).trigger('ajax:addToCart', {
                    productIds: [productId],
                    response: response
                });
                window.dispatchEvent(new CustomEvent('ai-assistant:product-added', { detail: response }));

                let msg = $t('The product %1 has been successfully added to the cart.').replace('%1', productName);
                this.pushStatusMessage('success', msg);
                this.sendMessage(null, msg, true, 'success');
            }).fail((xhr) => {
                let message = $t('Could not add product to cart.');

                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                this.pushStatusMessage('error', message);
            }).always(() => {
                this.addingToCart(null);
            });

            return false;
        },

        getFrontendTools: function () {
            return [
                {
                    name: 'front__redirectToCart',
                    description: 'Redirects the user to the shopping cart page. Use this ONLY when the user explicitly asks to go to the cart, view the cart, or finish their purchase. You MUST always send a friendly chat message alongside this call. NEVER use this tool for off-topic questions, greetings, or when the user is asking about anything unrelated to their shopping cart.'
                },
                {
                    name: 'front__navigateToPage',
                    description: 'Redirects the user to an internal store page. WARNING: Use this tool ONLY if an exact path has been explicitly provided in the active topic\'s STORE KNOWLEDGE BASE. NEVER guess or invent paths.',
                    parameters: {
                        type: 'OBJECT',
                        properties: {
                            path: {
                                type: 'STRING',
                                description: 'Exact relative page path provided in the store knowledge base.'
                            }
                        },
                        required: ['path']
                    }
                }
            ];
        },

        executeToolCalls: function (toolCalls) {
            if (!toolCalls || !Array.isArray(toolCalls) || this._isRedirecting) {
                return;
            }

            let self = this;
            let handlers = {
                'front__redirectToCart': function () {
                    if (self._isRedirecting) {
                        return;
                    }
                    self._isRedirecting = true;
                    window.location.href = urlBuilder.build('checkout/cart');
                },
                'front__navigateToPage': function (args) {
                    if (self._isRedirecting) {
                        return;
                    }
                    if (args && args.path) {
                        let cleanPath = String(args.path).trim().replace(/^https?:\/\/[^\/]+\//, '').replace(/^\/+/, '');
                        self._isRedirecting = true;
                        window.location.href = urlBuilder.build(cleanPath);
                    }
                }
            };

            const executed = new Set();
            for (let i = 0; i < toolCalls.length; i++) {
                let call = toolCalls[i];
                if (!call || !call.name || executed.has(call.name)) {
                    continue;
                }
                let handler = handlers[call.name];
                if (typeof handler === 'function') {
                    executed.add(call.name);
                    handler(call.args || {});
                    if (self._isRedirecting) {
                        break;
                    }
                }
            }
        },

        sendMessage: function (topicKey, textOverride, isSilent, status) {
            let text = (typeof textOverride === 'string' && textOverride.trim() !== '')
                ? textOverride.trim()
                : String(this.currentInput() || '').trim();

            if (!text || this.isLoading()) {
                return;
            }

            if (!isSilent) {
                this.addMessage('user', text);
            }

            // Clear suggestions from all previous messages
            const msgs = this.messages();
            for (let i = 0; i < msgs.length; i++) {
                if (ko.isObservable(msgs[i].suggested_replies)) {
                    msgs[i].suggested_replies([]);
                }
            }

            if (!textOverride) {
                this.currentInput('');
            }
            this.isLoading(true);
            this.scrollToBottom();

            let payload = { message: text, form_key: $.mage.cookies.get('form_key') || '' };
            if (topicKey && typeof topicKey === 'string') {
                payload.topic_context = topicKey;
            }

            payload.tools = this.getFrontendTools();
            if (isSilent) {
                payload.silent = true;
            }
            if (status) {
                payload.status = status;
            }

            storage.post(
                urlBuilder.build('ai-assistant/chat/send'),
                JSON.stringify(payload),
                true
            ).done((response) => {
                    if (response.success) {
                    const message = response.message || response.response || '';
                    const hasProducts = Array.isArray(response.products) && response.products.length;
                    const hasGroups = Array.isArray(response.groups) && response.groups.length;
                    const hasSuggestedReplies = Array.isArray(response.suggested_replies)
                        && response.suggested_replies.length;
                    const hasToolCalls = Array.isArray(response.tool_calls) && response.tool_calls.length;

                    if (message) {
                        this.addMessage('assistant', message, response);
                    } else if (!isSilent && (hasProducts || hasGroups || hasSuggestedReplies)) {
                        this.addMessage('assistant', '', response);
                    } else if (isSilent && hasSuggestedReplies) {
                        const msgs = this.messages();
                        const last = msgs[msgs.length - 1];
                        if (last && last.type === 'status' && ko.isObservable(last.suggested_replies)) {
                            last.suggested_replies(response.suggested_replies);
                        }
                    }

                    if (hasToolCalls) {
                        this.executeToolCalls(response.tool_calls);
                    }
                } else {
                    const statusResponse = Object.assign({
                        status: 'error',
                        suggested_replies: [{ label: $t('Try again'), text: $t('Try again') }]
                    }, response);
                    this.addMessage('status', response.message || $t('Could not get a response.'), statusResponse);
                }
            }).fail(() => {
                const statusResponse = {
                    status: 'error',
                    suggested_replies: [{ label: $t('Try again'), text: $t('Try again') }]
                };
                this.addMessage('status', $t('Sorry, an error occurred while querying the assistant.'), statusResponse);
            }).always(() => {
                this.isLoading(false);
                this.scrollToBottom();
                this.initGallerySwipers();
                this.syncSwiperLayout();
                if (this.chatState() !== 'minimized' && window.innerWidth > 768) {
                    setTimeout(() => {
                        const input = document.getElementById('ai-chat-input');
                        if (input) {
                            input.focus();
                        }
                    }, 50);
                }
            });
        }
    });
});
