define(['exports'], (function (exports) { 'use strict';

    class Response {
        constructor(data) {
            this.data = data;
        }

        /**
         * @returns {Boolean}
         */
        getResult()
        {
            return Boolean(this.data['result']);
        }

        /**
         * @return {null|String}
         */
        getTransactionId()
        {
            if (typeof this.data['transaction'] !== 'undefined' &&
                typeof this.data['transaction']['transactionId'] !== 'undefined') {
                return this.data['transaction']['transactionId'];
            }

            return null;
        }

        /**
         * @return {null|String}
         */
        getEntranceCode()
        {
            if (typeof this.data['transaction'] !== 'undefined' &&
                typeof this.data['transaction']['entranceCode'] !== 'undefined') {
                return this.data['transaction']['entranceCode'];
            }

            return null;
        }

        /**
         * @returns {String|null}
         */
        getThreeDSTransactionId() {
            if (typeof this.data['transactionID'] === 'undefined') {
                return null;
            }

            return this.data['transactionID'];
        }

        /**
         * @returns {String|null}
         */
        getAcquirerId() {
            if (typeof this.data['acquirerID'] === 'undefined') {
                return null;
            }

            return this.data['acquirerID'];
        }

        /**
         * @returns {String|null}
         */
        getTdsApiUrlStatus() {
            if (typeof this.data['tdsApiURLStatus'] === 'undefined') {
                return null;
            }

            return this.data['tdsApiURLStatus'];
        }
    }

    class ActionableResponse extends Response {
        /**
         * @param {Object} data
         */
        constructor(data) {
            super(data);
        }

        /**
         * @returns {String}
         */
        getNextAction()
        {
            return this.data['nextAction'];
        }
    }

    class GenericEvent
    {
        constructor(subject, parameters = {}) {
            this.subject = subject;
            this.parameters = parameters;
            this.propagationStopped = false;
        }

        getSubject()
        {
            return this.subject;
        }

        getParameter(key)
        {
            if (this.hasParameter(key)) {
                return this.parameters[key];
            }

            throw new Error('Parameter "' + key + '" was not found.');
        }

        setParameter(key, value)
        {
            this.parameters[key] = value;

            return this;
        }

        setParameters(value)
        {
            this.parameters = value;

            return this;
        }

        getParameters()
        {
            return this.parameters;
        }

        hasParameter(key)
        {
            return (key in this.parameters);
        }

        isPropagationStopped()
        {
            return this.propagationStopped;
        }

        stopPropagation()
        {
            this.propagationStopped = true;
        }
    }

    class ActionableResponseEvent extends GenericEvent {}

    class Events$1 {
        static get form()
        {
            return 'form';
        }

        static get creditCardImage()
        {
            return 'creditCardImage';
        }

        static get tdsMethodPlaceholderId()
        {
            return 'payment_tds_method_placeholder';
        }

        static get creditCardHolder()
        {
            return 'creditCardHolder';
        }

        static get creditCardNumber()
        {
            return 'creditCardNumber';
        }

        static get creditCardCvv()
        {
            return 'creditCardCvv';
        }

        static get creditCardCvvLabel()
        {
            return 'creditCardCvvLabel';
        }

        static get creditCardExpiration()
        {
            return 'creditCardExpiration';
        }

        static get creditCardExpirationMonth()
        {
            return 'creditCardExpirationMonth';
        }

        static get creditCardExpirationYear()
        {
            return 'creditCardExpirationYear';
        }

        static get submitButton()
        {
            return 'submitButton';
        }

        static get modalContainer()
        {
            return 'modalContainer';
        }
    }

    class ElementReferences
    {
        constructor(config)
        {
            this.config = config;
            this.references = {};
            this.lookup = {};

            let self = this;

            this.lookup[Events$1.form] = function () {
                return document.querySelector('form[' + self.config.form_selector + ']');
            };
            this.lookup[Events$1.creditCardImage] = function () {
                return self.getElementByQuerySelector('img[data-credit-card-type]');
            };
            this.lookup[Events$1.creditCardHolder] = function(){
                return self.getElementByQuerySelector('[name="cardholder"]');
            };
            this.lookup[Events$1.creditCardNumber] = function(){
                return self.getElementByQuerySelector('[name="cardnumber"]');
            };
            this.lookup[Events$1.creditCardCvv] = function(){
                return self.getElementByQuerySelector('[name="cardcvc"]');
            };
            this.lookup[Events$1.creditCardCvvLabel] = function(){
                return self.getElementByQuerySelector('[data-cvc-label]');
            };
            this.lookup[Events$1.creditCardExpiration] = function(){
                return self.getElementByQuerySelector('[name="expiration"]');
            };
            this.lookup[Events$1.creditCardExpirationMonth] = function(){
                return self.getElementByQuerySelector('[name="valid_thru_month"]');
            };
            this.lookup[Events$1.creditCardExpirationYear] = function(){
                return self.getElementByQuerySelector('[name="valid_thru_year"]');
            };
            this.lookup[Events$1.submitButton] = function(){
                return self.getElementByQuerySelector('[type="submit"]');
            };
            this.lookup[Events$1.modalContainer] = function(){
                return document.getElementById('payment-modal');
            };
            this.lookup[Events$1.tdsMethodPlaceholderId] = function(){
                return document.getElementById(Events$1.tdsMethodPlaceholderId);
            };
        }

        /**
         * Get the reference to the DOM element.
         *
         * @param key
         * @return {*}
         */
        getReference(key)
        {
            if (!(key in this.references) && !(key in this.lookup)) {
                throw new Error('Unknown element reference.');
            }

            if (undefined !== this.references[key]) {
                return this.references[key];
            }

            this.references[key] = this.lookup[key]();

            return this.references[key];
        }

        /**
         * Return element via query selector within the form.
         *
         * @param selector
         * @return {*}
         */
        getElementByQuerySelector(selector) {
            return this.getReference(Events$1.form).querySelector(selector);
        }
    }

    class State {
        constructor() {
            this.state = {
                loading: false,
                inputComplete: false,
                cardHolderInputComplete: false,
                cardNumberInputComplete: false,
                cardCvvInputComplete: false,
                cardExpiryMonthInputComplete: false,
                cardExpiryYearInputComplete: false,
                formReadyForSubmission: false,
                formSubmitted: false,
                modalOpen: false,
                isPolling: false,
                isPaid: false,
                isAuthenticatingTds: false,
                entranceCode: null,
                transactionId: null,
                threeDSTransactionId: null,
                acquirerId: null,
                challengeRetry: false,
                challengeTime: null,
                challengeTimeout: 120,
                payload: null
            };

            this.config = {};
            this.elements = {};
        }

        getConfig()
        {
            return this.config;
        }

        setConfig(config) {
            this.config = config;
        }



        getElementFromReference(key)
        {
            if (!(this.elements instanceof ElementReferences)) {
                this.elements = new ElementReferences(this.config);
            }

            return this.elements.getReference(key);
        }

        getCurrentState() {
            return this.state;
        }

        updateState(data) {
            if (typeof this.state !== 'object') {
                throw new Error('Unable to update state, expected an object but received ' + typeof this.state + '.');
            }

            for (const [key, value] of Object.entries(data)) {
                this.state[key] = value;
            }

            return this;
        }

        getStateParameter(key) {
            if (key in this.state) {
                return this.state[key];
            }

            return null;
        }

        setStateParameter(key, value) {
            this.state[key] = value;
            return this;
        }

        static getInstance() {
            if (!State.instance) {
                State.instance = new State();
            }

            return State.instance;
        }

        isModalOpen()
        {
            return this.state.modalOpen === true;
        }

        isModalClosed()
        {
            return this.state.modalOpen === false;
        }

        isFormReadyForSubmission()
        {
            return this.state.formReadyForSubmission && !this.state.formSubmitted;
        }

        isFormSubmitted()
        {
            return this.state.formSubmitted;
        }

        isLoading()
        {
            return this.state.loading;
        }

        isPolling()
        {
            return this.state.isPolling;
        }

        isPaid()
        {
            return this.state.isPaid;
        }

        isChallengeRetry()
        {
            return this.state.challengeRetry;
        }

        isChallengeRetryValid()
        {
            if (this.state.transactionId === null || this.state.threeDSTransactionId === null) {
                return false;
            }

            return this.state.challengeRetry
                && (this.state.challengeTime + this.state.challengeTimeout) > Math.floor(Date.now() / 1000);
        }
    }

    class Exception extends Error {}

    class BreakException extends Exception {}

    class Events {
        /**
         * When requests are made.
         *
         * @return {string}
         */
        static get onRequestEvent()
        {
            return 'onRequestEvent';
        }

        /**
         * When we passed the authentication stage and want to authorize this payment.
         *
         * @return {string}
         */
        static get onPaymentAuthorizeEvent()
        {
            return 'onPaymentAuthorizeEvent';
        }

        /**
         * Response is received.
         *
         * @return {string}
         */
        static get onResponseEvent()
        {
            return 'onResponseEvent';
        }

        /**
         * Polling response is received.
         *
         * @return {string}
         */
        static get onPollingResponseEvent()
        {
            return 'onPollingResponseEvent';
        }

        /**
         * Response factory creates the appropriate response objects.
         *
         * @return {string}
         */
        static get onResponseFactoryEvent()
        {
            return 'onResponseFactoryEvent';
        }

        /**
         * When state changes, albeit form input, submitting the form etc.
         *
         * @return {string}
         */
        static get onStateChangeEvent()
        {
            return 'onStateChangeEvent';
        }

        /**
         * When we have a response that requires a modal for display, we want a merchant to be able to create custom modals.
         *
         * @return {string}
         */
        static get onResolveModalEvent()
        {
            return 'onResolveModalEvent';
        }

        /**
         * When we are opening a model.
         *
         * @return {string}
         */
        static get onModalOpenEvent()
        {
            return 'onModalOpenEvent';
        }

        /**
         * When we are closing a model.
         *
         * @return {string}
         */
        static get onModalCloseEvent()
        {
            return 'onModalCloseEvent';
        }

        /**
         * Used for the debugger. Events are always fired but only are dispatched if debugging is on.
         *
         * @return {string}
         */
        static get onDebugEvent()
        {
            return 'onDebugEvent';
        }

        /**
         * TDS Method should be triggered.
         *
         * @return {string}
         */
        static get onPaymentRequiresTdsMethodEvent()
        {
            return 'onPaymentRequiresTdsMethodEvent';
        }

        /**
         * Trigger authentication, used within the TDS method.
         *
         * @return {string}
         */
        static get onPaymentAuthenticateEvent()
        {
            return 'onPaymentAuthenticateEvent';
        }

        /**
         * Triggered from authenticate or authorize responses, these contain information about the next step.
         *
         * @return {string}
         */
        static get onActionableResponseEvent()
        {
            return 'onActionableResponseEvent';
        }

        /**
         * Triggered from the last redirect within the IFrame, by an CustomEvent. This recovers the form for re-submission
         * and presets the user a given error.
         *
         * @return {string}
         */
        static get onPaymentFailedEvent()
        {
            return 'onPaymentFailedEvent';
        }

        /**
         * Triggered when the user cancels a payment.
         *
         * @return {string}
         */
        static get onPaymentCanceledEvent()
        {
            return 'onPaymentCanceledEvent';
        }

        /**
         * Triggered from the last redirect within the IFrame, by an CustomEvent. This immediately gets triggered with the
         * appropriate event. Also used when a non-3ds payment was completed successfully.
         *
         * @return {string}
         */
        static get onPaymentCompleteEvent()
        {
            return 'onPaymentCompleteEvent';
        }

        /**
         * Triggered when 3DS validation of the card is required.
         *
         * @return {string}
         */
        static get onPaymentRequiresChallengeEvent()
        {
            return 'onPaymentRequiresChallengeEvent';
        }

        /**
         * When we are about to submit the form data, extra data can be added next to the encrypted payload.
         *
         * @return {string}
         */
        static get onSubmitDataEvent()
        {
            return 'onSubmitDataEvent';
        }

        /**
         * Before the modal body is about to be rendered, allows wrapping the body with a container.
         *
         * @return {string}
         */
        static get onBeforeDisplayModalEvent()
        {
            return 'onBeforeDisplayModalEvent';
        }
    }

    class EventDispatcher {
        constructor() {
            this.instance = null;
            this.listeners = [];
            this.sorted = [];
        }

        /**
         * @returns {EventDispatcher}
         */
        static getInstance() {
            if (!EventDispatcher.instance) {
                EventDispatcher.instance = new EventDispatcher();
            }

            return EventDispatcher.instance;
        }

        /**
         * Get listeners for a certain event.
         *
         * @param eventName
         * @returns {[EventListener]}
         */
        getListeners(eventName)
        {
            if (eventName) {
                if (!Array.isArray(this.listeners[eventName]) || 0 === this.listeners[eventName].length) {
                    return [];
                }

                if (!(eventName in this.sorted) || 0 === this.sorted[eventName].length) {
                    this.sortListeners(eventName);
                }

                return this.sorted[eventName];
            }

            // eslint-disable-next-line
            for (const [eventName, listeners] of Object.entries(this.listeners)) {
                if (!(eventName in this.sorted)) {
                    this.sortListeners(eventName);
                }
            }

            return this.sorted;
        }

        /**
         * Add event listener.
         *
         * @param eventName
         * @param listener
         * @param priority
         * @returns {EventDispatcher}
         */
        addListener(eventName, listener, priority = 0)
        {
            if (priority < 0) {
                throw new Error('Priority must be a non-negative index.');
            }

            if (!(eventName in this.listeners)) {
                this.listeners[eventName] = [];
            }

            if (!(priority in this.listeners[eventName])) {
                this.listeners[eventName][priority] = [];
            }

            this.listeners[eventName][priority].push(listener);

            if (eventName in this.sorted) {
                delete this.sorted[eventName];
            }

            return this;
        }

        /**
         * Remove an event listener.
         *
         * @param eventName
         * @param listener
         */
        removeListener(eventName, listener)
        {
            if (!this.listeners[eventName]) {
                return;
            }

            for (const [priority, listeners] of Object.entries(this.listeners[eventName])) {
                for (const [key, value] of Object.entries(listeners)) {
                    if (Array.isArray(value) && value.length === 2) {
                        if (value[0] === listener[0] && value[1] === listener[1]) {
                            delete this.listeners[eventName][priority][key];
                            delete this.sorted[eventName];
                        }
                    }

                    if (value === listener) {
                        delete this.listeners[eventName][priority][key];
                        delete this.sorted[eventName];
                    }
                }

                if (Object.entries(this.listeners[eventName][priority]).length === 0) {
                    delete this.listeners[eventName][priority];
                }
            }
        }

        /**
         * Dispatch an event.
         *
         * @param event
         * @param eventName
         * @returns {GenericEvent}
         */
        dispatch(event, eventName)
        {
            let listeners = this.getListeners(eventName);

            if (listeners.length > 0) {
                this.callListeners(listeners, eventName, event);
            }

            return event;
        }

        /**
         * Call listeners.
         *
         * @param listeners
         * @param eventName
         * @param event
         */
        callListeners(listeners, eventName, event)
        {
            try {
                listeners.forEach(function(listener){
                    if (event.propagationStopped) {
                        throw new BreakException();
                    }

                    if (Array.isArray(listener) && listener.length === 2) {
                        if (typeof listener[0][listener[1]] === 'function') {
                            listener[0][listener[1]](event, eventName, this);
                        } else {
                            throw new Error('Could not call method on listener instance.');
                        }
                    } else {
                        listener(event, eventName, this);
                    }
                });
            } catch (e) {
                if (e instanceof BreakException) {
                    return;
                }

                throw e;
            }
        }

        /**
         * Check if there are listeners for a certain event.
         *
         * @param eventName
         * @returns {*|boolean}
         */
        hasListeners(eventName = null)
        {
            if (eventName) {
                return this.listeners[eventName] && this.sorted[eventName].length > 0;
            }

            return this.listeners.length > 0;
        }

        /**
         * Sort listeners by priority.
         *
         * @param eventName
         * @returns {[EventListener]}
         */
        sortListeners(eventName)
        {
            this.sorted[eventName] = [];

            let listeners = this.listeners[eventName];
            let sortedKeys = Object.keys(listeners).sort((a, b) => a - b);

            for (const sortedListenerKey of sortedKeys) {
                for (const listener of listeners[sortedListenerKey]) {
                    this.sorted[eventName].push(listener);
                }
            }

            return this.sorted;
        }

        /**
         * Get the priority for a given listener.
         *
         * @param eventName
         * @param listener
         * @returns {null|*}
         */
        getListenerPriority(eventName, listener)
        {
            if (!this.listeners[eventName]) {
                return;
            }

            for (const [priority, listeners] of Object.entries(this.listeners[eventName])) {
                for (const l of listeners) {
                    if (Array.isArray(l) && l.length === 2) {
                        if (l[0] === listener[0] && l[1] === listener[1]) {
                            return priority;
                        }
                    }
                    if (l === listener) {
                        return priority;
                    }
                }
            }

            return null;
        }
    }

    class EventListener {
        /**
         * Retrieve event dispatcher.
         * @returns {EventDispatcher}
         */
        getEventDispatcher()
        {
            return EventDispatcher.getInstance();
        }

        /**
         * Retrieve current state.
         * @returns {State}
         */
        getState()
        {
            return State.getInstance();
        }
    }

    class PaymentRequiresChallengeEvent extends GenericEvent {}

    class PaymentCompleteEvent extends GenericEvent {
        constructor(subject, parameters = {}) {
            parameters = Object.assign({}, {
                redirection_enabled: true,
                redirection_url: null,
                redirection_timeout: 2500
            }, parameters);

            super(subject, parameters);
        }
    }

    class PollingResponseEvent extends GenericEvent {}

    class PollingResponse extends Response {
        /**
         * @param {Object} data
         */
        constructor(data) {
            super(data);
        }

        /**
         * @returns {Number|null}
         */
        getTransactionStatusCode() {
            if (typeof this.data['transactionStatusCode'] === 'undefined') {
                return null;
            }

            return Number(this.data['transactionStatusCode']);
        }

        /**
         * @returns {String|null}
         */
        getTransactionId() {
            if (typeof this.data['transactionID'] === 'undefined') {
                return null;
            }

            return this.data['transactionID'];
        }
    }

    class ErrorResponse extends ActionableResponse {
        /**
         * @param {Object} data
         */
        constructor(data) {
            super(data);
        }

        /**
         * @return {String}
         */
        getErrorMessage() {
            return this.data['errorMessage'];
        }

        /**
         * @return {Number}
         */
        getErrorCode() {
            return Number(this.data['errorCode']);
        }

        /**
         * @return {String}
         */
        getErrorTag() {
            return this.data['errorCode'];
        }

        /**
         * @returns {Number}
         */
        getStatus() {
            return this.data['status'];
        }

        /**
         * @returns {String}
         */
        getMessage() {
            return this.getErrorMessage();
        }

        /**
         * @returns {String}
         */
        getLevel() {
            return this.data['level'];
        }
    }

    class PaymentFailedEvent extends GenericEvent {}

    class PaymentRequiresTdsMethodEvent extends ActionableResponseEvent {}

    class StateChangeEvent extends GenericEvent {
        getCurrentState()
        {
            return State.getInstance();
        }
    }

    class ActionableResponseListener extends EventListener {
        static onActionableResponse(event)
        {
            console.log('onActionableResponse evebt');

            let response = event.getSubject();
            let nextAction = response.getNextAction();
            let config = event.getParameter('config');
            let encryptedForm = event.getParameter('encryptedForm');
            let poller = event.getParameter('poller');

            poller.clear();

            if (response instanceof ErrorResponse) {
                let paymentFailedEvent = new PaymentFailedEvent(response, {
                    'encryptedForm': encryptedForm,
                    'originalEvent': event,
                    'paymentCompleteUrl': config.payment_complete_url
                });

                console.log('mission.. is a failure');
                EventDispatcher.getInstance().dispatch(
                    paymentFailedEvent,
                    Events.onPaymentFailedEvent
                );
            }

            console.log('nextAction: ' + nextAction);

            if ('challenged' === nextAction || 'tdsMethod' === nextAction)
            {
                console.log('start poll ');
                poller.poll(config.status_url.replace('%transaction_id%', response.getThreeDSTransactionId()), (url) => {
                    fetch(url, {
                        'method': 'GET',
                        'cache': 'no-cache',
                    }).then((response) => {
                        if (response.status !== 200) {
                            throw Error('Unexpected status code returned');
                        }

                        return response.json().catch(() => {
                            throw new Error('Invalid JSON returned.');
                        });
                    }).then((json) => {
                        let pollEvent = new PollingResponseEvent(
                            new PollingResponse(json),
                            {
                                'encryptedForm': encryptedForm,
                                'config': config,
                                'poller': poller
                            }
                        );

                        EventDispatcher.getInstance().dispatch(pollEvent, Events.onPollingResponseEvent);
                    }).catch((exception) => {
                        console.log('catch exit()');
                        poller.clear();

                        EventDispatcher.getInstance().dispatch(
                            new PaymentFailedEvent(response, {
                                'encryptedForm': encryptedForm,
                                'originalEvent': event,
                                'paymentCompleteUrl': config.payment_complete_url
                            }),
                            Events.onPaymentFailedEvent
                        );
                    });
                });
            }

            switch (nextAction) {
                case 'challenged':
                    EventDispatcher.getInstance().dispatch(
                        new PaymentRequiresChallengeEvent(response, {
                            'poller': poller,
                            'encryptedForm': encryptedForm
                        }),
                        Events.onPaymentRequiresChallengeEvent
                    );

                    EventDispatcher.getInstance().dispatch(new StateChangeEvent(event, {
                        'state': {
                            'challengeTime': Math.floor(Date.now() / 1000)
                        }
                    }), Events.onStateChangeEvent);

                    break;
                case 'tdsMethod':
                    EventDispatcher.getInstance().dispatch(
                        new PaymentRequiresTdsMethodEvent(response, {
                            'poller': poller,
                            'encryptedForm': encryptedForm
                        }),
                        Events.onPaymentRequiresTdsMethodEvent
                    );
                    break;
                case 'paid':
                case 'verify':
                case 'redirectToShop':
                    EventDispatcher.getInstance().dispatch(
                        new PaymentCompleteEvent(response, {
                            'encryptedForm': encryptedForm,
                            'paymentCompleteUrl': config.payment_complete_url,
                            'urlParameters': {
                                'orderId': response.data.orderId,
                                'entranceCode': response.data.entranceCode,
                                'sueprArg' : 12345,
                            }
                        }),
                        Events.onPaymentCompleteEvent
                    );
                    break;
                case 'retryChallenge':
                    EventDispatcher.getInstance().dispatch(new StateChangeEvent(event, {
                        'state': {
                            'challengeRetry': true
                        }
                    }), Events.onStateChangeEvent);
                    break;
            }
        }
    }

    class ModalCloseEvent extends GenericEvent {}

    class Modal {
        /**
         * @param {Response|ChallengedResponse|TransactionResponse|ErrorResponse} response
         */
        constructor(response, config = {}) {
            this.response = response;
            this.config = Object.assign({}, {
                onShow: () => {
                    EventDispatcher.getInstance().dispatch(new StateChangeEvent(null, {
                        'state': {modalOpen: true}
                    }), Events.onStateChangeEvent);
                },
                onClose: () => {
                    let modalContainer = State.getInstance().getElementFromReference(Events$1.modalContainer);
                    let stateUpdates = {modalOpen: false};

                    if (State.getInstance().isFormSubmitted()) {
                        stateUpdates['formSubmitted'] = false;
                    }

                    EventDispatcher.getInstance().dispatch(new StateChangeEvent(null, {
                        'state': stateUpdates,
                    }), Events.onStateChangeEvent);

                    EventDispatcher.getInstance().dispatch(new ModalCloseEvent(this, {
                        'manual': true
                    }), Events.onModalCloseEvent);

                    if (typeof modalContainer !== 'undefined') {
                        modalContainer.innerHTML = '';
                    }
                },
                awaitOpenAnimation: false,
                awaitCloseAnimation: false
            }, config);
        }

        getConfig() {
            return this.config;
        }
    }

    // eslint-disable-next-line no-unused-vars

    class AuthorizingModal extends Modal {
        /**
         * @param {PollingResponse} response
         */
        constructor(response) {
            super(response);
        }

        // eslint-disable-next-line
        render(parameters = {}) {
            return 'Authorizing...';
        }
    }

    class ChallengedResponse extends ActionableResponse {
        /**
         * @param {Object} data
         */
        constructor(data) {
            super(data);
        }

        /**
         * Not implemented
         *
         * @returns {Boolean}
         */
        isRedirectionChallenge()
        {
            if (typeof this.data['challengeType'] === 'undefined') {
                return false;
            }

            return this.data['challengeType'] === 'redirect';
        }

        /**
         * @returns {Boolean}
         */
        isIFrameChallenge()
        {
            if (typeof this.data['challengeType'] === 'undefined') {
                return false;
            }

            return this.data['challengeType'] === 'iframe';
        }

        /**
         * @returns {String}
         */
        getChallengeType() {
            return this.data['challengeType'];
        }

        /**
         * @returns {String}
         */
        getChallengeForm() {
            return this.data['challengeForm'];
        }

        /**
         * @returns {String}
         */
        getChallengeWindowWidth() {
            return this.data['challengeWindowWidth'];
        }

        /**
         * @returns {String}
         */
        getChallengeWindowHeight() {
            return this.data['challengeWindowHeight'];
        }

        /**
         * @returns {String}
         */
        getTdsMode() {
            return this.data['tdsMode'];
        }

        /**
         * @return {String}
         */
        getType() {
            return 'challenged';
        }
    }

    // eslint-disable-next-line no-unused-vars

    class ChallengeModal extends Modal {
        /**
         * @param {ChallengedResponse} response
         */
        constructor(response) {
            super(response);
        }

        // eslint-disable-next-line
        render(parameters = {}) {
            // @todo is there any different way that is more solid to achieve this?
            let html = '<html><head></head><body>' + atob(this.response.getChallengeForm()) + '<script type="text/javascript">(function(){ document.querySelector("form").submit() })();</script></body></html>';
            return '<iframe id="challenge-iframe" src="data:text/html;charset=utf-8,' + encodeURI(html) + '"></iframe>';
        }
    }

    var commonjsGlobal$1 = typeof window !== 'undefined' ? window : typeof global !== 'undefined' ? global : typeof self !== 'undefined' ? self : {};

    var NumeralFormatter = function (numeralDecimalMark,
                                     numeralIntegerScale,
                                     numeralDecimalScale,
                                     numeralThousandsGroupStyle,
                                     numeralPositiveOnly,
                                     stripLeadingZeroes,
                                     prefix,
                                     signBeforePrefix,
                                     tailPrefix,
                                     delimiter) {
        var owner = this;

        owner.numeralDecimalMark = numeralDecimalMark || '.';
        owner.numeralIntegerScale = numeralIntegerScale > 0 ? numeralIntegerScale : 0;
        owner.numeralDecimalScale = numeralDecimalScale >= 0 ? numeralDecimalScale : 2;
        owner.numeralThousandsGroupStyle = numeralThousandsGroupStyle || NumeralFormatter.groupStyle.thousand;
        owner.numeralPositiveOnly = !!numeralPositiveOnly;
        owner.stripLeadingZeroes = stripLeadingZeroes !== false;
        owner.prefix = (prefix || prefix === '') ? prefix : '';
        owner.signBeforePrefix = !!signBeforePrefix;
        owner.tailPrefix = !!tailPrefix;
        owner.delimiter = (delimiter || delimiter === '') ? delimiter : ',';
        owner.delimiterRE = delimiter ? new RegExp('\\' + delimiter, 'g') : '';
    };

    NumeralFormatter.groupStyle = {
        thousand: 'thousand',
        lakh:     'lakh',
        wan:      'wan',
        none:     'none'
    };

    NumeralFormatter.prototype = {
        getRawValue: function (value) {
            return value.replace(this.delimiterRE, '').replace(this.numeralDecimalMark, '.');
        },

        format: function (value) {
            var owner = this, parts, partSign, partSignAndPrefix, partInteger, partDecimal = '';

            // strip alphabet letters
            value = value.replace(/[A-Za-z]/g, '')
                // replace the first decimal mark with reserved placeholder
                .replace(owner.numeralDecimalMark, 'M')

                // strip non numeric letters except minus and "M"
                // this is to ensure prefix has been stripped
                .replace(/[^\dM-]/g, '')

                // replace the leading minus with reserved placeholder
                .replace(/^\-/, 'N')

                // strip the other minus sign (if present)
                .replace(/\-/g, '')

                // replace the minus sign (if present)
                .replace('N', owner.numeralPositiveOnly ? '' : '-')

                // replace decimal mark
                .replace('M', owner.numeralDecimalMark);

            // strip any leading zeros
            if (owner.stripLeadingZeroes) {
                value = value.replace(/^(-)?0+(?=\d)/, '$1');
            }

            partSign = value.slice(0, 1) === '-' ? '-' : '';
            if (typeof owner.prefix != 'undefined') {
                if (owner.signBeforePrefix) {
                    partSignAndPrefix = partSign + owner.prefix;
                } else {
                    partSignAndPrefix = owner.prefix + partSign;
                }
            } else {
                partSignAndPrefix = partSign;
            }

            partInteger = value;

            if (value.indexOf(owner.numeralDecimalMark) >= 0) {
                parts = value.split(owner.numeralDecimalMark);
                partInteger = parts[0];
                partDecimal = owner.numeralDecimalMark + parts[1].slice(0, owner.numeralDecimalScale);
            }

            if(partSign === '-') {
                partInteger = partInteger.slice(1);
            }

            if (owner.numeralIntegerScale > 0) {
                partInteger = partInteger.slice(0, owner.numeralIntegerScale);
            }

            switch (owner.numeralThousandsGroupStyle) {
                case NumeralFormatter.groupStyle.lakh:
                    partInteger = partInteger.replace(/(\d)(?=(\d\d)+\d$)/g, '$1' + owner.delimiter);

                    break;

                case NumeralFormatter.groupStyle.wan:
                    partInteger = partInteger.replace(/(\d)(?=(\d{4})+$)/g, '$1' + owner.delimiter);

                    break;

                case NumeralFormatter.groupStyle.thousand:
                    partInteger = partInteger.replace(/(\d)(?=(\d{3})+$)/g, '$1' + owner.delimiter);

                    break;
            }

            if (owner.tailPrefix) {
                return partSign + partInteger.toString() + (owner.numeralDecimalScale > 0 ? partDecimal.toString() : '') + owner.prefix;
            }

            return partSignAndPrefix + partInteger.toString() + (owner.numeralDecimalScale > 0 ? partDecimal.toString() : '');
        }
    };

    var NumeralFormatter_1 = NumeralFormatter;

    var DateFormatter = function (datePattern, dateMin, dateMax) {
        var owner = this;

        owner.date = [];
        owner.blocks = [];
        owner.datePattern = datePattern;
        owner.dateMin = dateMin
            .split('-')
            .reverse()
            .map(function(x) {
                return parseInt(x, 10);
            });
        if (owner.dateMin.length === 2) owner.dateMin.unshift(0);

        owner.dateMax = dateMax
            .split('-')
            .reverse()
            .map(function(x) {
                return parseInt(x, 10);
            });
        if (owner.dateMax.length === 2) owner.dateMax.unshift(0);

        owner.initBlocks();
    };

    DateFormatter.prototype = {
        initBlocks: function () {
            var owner = this;
            owner.datePattern.forEach(function (value) {
                if (value === 'Y') {
                    owner.blocks.push(4);
                } else {
                    owner.blocks.push(2);
                }
            });
        },

        getISOFormatDate: function () {
            var owner = this,
                date = owner.date;

            return date[2] ? (
                date[2] + '-' + owner.addLeadingZero(date[1]) + '-' + owner.addLeadingZero(date[0])
            ) : '';
        },

        getBlocks: function () {
            return this.blocks;
        },

        getValidatedDate: function (value) {
            var owner = this, result = '';

            value = value.replace(/[^\d]/g, '');

            owner.blocks.forEach(function (length, index) {
                if (value.length > 0) {
                    var sub = value.slice(0, length),
                        sub0 = sub.slice(0, 1),
                        rest = value.slice(length);

                    switch (owner.datePattern[index]) {
                        case 'd':
                            if (sub === '00') {
                                sub = '01';
                            } else if (parseInt(sub0, 10) > 3) {
                                sub = '0' + sub0;
                            } else if (parseInt(sub, 10) > 31) {
                                sub = '31';
                            }

                            break;

                        case 'm':
                            if (sub === '00') {
                                sub = '01';
                            } else if (parseInt(sub0, 10) > 1) {
                                sub = '0' + sub0;
                            } else if (parseInt(sub, 10) > 12) {
                                sub = '12';
                            }

                            break;
                    }

                    result += sub;

                    // update remaining string
                    value = rest;
                }
            });

            return this.getFixedDateString(result);
        },

        getFixedDateString: function (value) {
            var owner = this, datePattern = owner.datePattern, date = [],
                dayIndex = 0, monthIndex = 0, yearIndex = 0,
                dayStartIndex = 0, monthStartIndex = 0, yearStartIndex = 0,
                day, month, year, fullYearDone = false;

            // mm-dd || dd-mm
            if (value.length === 4 && datePattern[0].toLowerCase() !== 'y' && datePattern[1].toLowerCase() !== 'y') {
                dayStartIndex = datePattern[0] === 'd' ? 0 : 2;
                monthStartIndex = 2 - dayStartIndex;
                day = parseInt(value.slice(dayStartIndex, dayStartIndex + 2), 10);
                month = parseInt(value.slice(monthStartIndex, monthStartIndex + 2), 10);

                date = this.getFixedDate(day, month, 0);
            }

            // yyyy-mm-dd || yyyy-dd-mm || mm-dd-yyyy || dd-mm-yyyy || dd-yyyy-mm || mm-yyyy-dd
            if (value.length === 8) {
                datePattern.forEach(function (type, index) {
                    switch (type) {
                        case 'd':
                            dayIndex = index;
                            break;
                        case 'm':
                            monthIndex = index;
                            break;
                        default:
                            yearIndex = index;
                            break;
                    }
                });

                yearStartIndex = yearIndex * 2;
                dayStartIndex = (dayIndex <= yearIndex) ? dayIndex * 2 : (dayIndex * 2 + 2);
                monthStartIndex = (monthIndex <= yearIndex) ? monthIndex * 2 : (monthIndex * 2 + 2);

                day = parseInt(value.slice(dayStartIndex, dayStartIndex + 2), 10);
                month = parseInt(value.slice(monthStartIndex, monthStartIndex + 2), 10);
                year = parseInt(value.slice(yearStartIndex, yearStartIndex + 4), 10);

                fullYearDone = value.slice(yearStartIndex, yearStartIndex + 4).length === 4;

                date = this.getFixedDate(day, month, year);
            }

            // mm-yy || yy-mm
            if (value.length === 4 && (datePattern[0] === 'y' || datePattern[1] === 'y')) {
                monthStartIndex = datePattern[0] === 'm' ? 0 : 2;
                yearStartIndex = 2 - monthStartIndex;
                month = parseInt(value.slice(monthStartIndex, monthStartIndex + 2), 10);
                year = parseInt(value.slice(yearStartIndex, yearStartIndex + 2), 10);

                fullYearDone = value.slice(yearStartIndex, yearStartIndex + 2).length === 2;

                date = [0, month, year];
            }

            // mm-yyyy || yyyy-mm
            if (value.length === 6 && (datePattern[0] === 'Y' || datePattern[1] === 'Y')) {
                monthStartIndex = datePattern[0] === 'm' ? 0 : 4;
                yearStartIndex = 2 - 0.5 * monthStartIndex;
                month = parseInt(value.slice(monthStartIndex, monthStartIndex + 2), 10);
                year = parseInt(value.slice(yearStartIndex, yearStartIndex + 4), 10);

                fullYearDone = value.slice(yearStartIndex, yearStartIndex + 4).length === 4;

                date = [0, month, year];
            }

            date = owner.getRangeFixedDate(date);
            owner.date = date;

            var result = date.length === 0 ? value : datePattern.reduce(function (previous, current) {
                switch (current) {
                    case 'd':
                        return previous + (date[0] === 0 ? '' : owner.addLeadingZero(date[0]));
                    case 'm':
                        return previous + (date[1] === 0 ? '' : owner.addLeadingZero(date[1]));
                    case 'y':
                        return previous + (fullYearDone ? owner.addLeadingZeroForYear(date[2], false) : '');
                    case 'Y':
                        return previous + (fullYearDone ? owner.addLeadingZeroForYear(date[2], true) : '');
                }
            }, '');

            return result;
        },

        getRangeFixedDate: function (date) {
            var owner = this,
                datePattern = owner.datePattern,
                dateMin = owner.dateMin || [],
                dateMax = owner.dateMax || [];

            if (!date.length || (dateMin.length < 3 && dateMax.length < 3)) return date;

            if (
                datePattern.find(function(x) {
                    return x.toLowerCase() === 'y';
                }) &&
                date[2] === 0
            ) return date;

            if (dateMax.length && (dateMax[2] < date[2] || (
                dateMax[2] === date[2] && (dateMax[1] < date[1] || (
                    dateMax[1] === date[1] && dateMax[0] < date[0]
                ))
            ))) return dateMax;

            if (dateMin.length && (dateMin[2] > date[2] || (
                dateMin[2] === date[2] && (dateMin[1] > date[1] || (
                    dateMin[1] === date[1] && dateMin[0] > date[0]
                ))
            ))) return dateMin;

            return date;
        },

        getFixedDate: function (day, month, year) {
            day = Math.min(day, 31);
            month = Math.min(month, 12);
            year = parseInt((year || 0), 10);

            if ((month < 7 && month % 2 === 0) || (month > 8 && month % 2 === 1)) {
                day = Math.min(day, month === 2 ? (this.isLeapYear(year) ? 29 : 28) : 30);
            }

            return [day, month, year];
        },

        isLeapYear: function (year) {
            return ((year % 4 === 0) && (year % 100 !== 0)) || (year % 400 === 0);
        },

        addLeadingZero: function (number) {
            return (number < 10 ? '0' : '') + number;
        },

        addLeadingZeroForYear: function (number, fullYearMode) {
            if (fullYearMode) {
                return (number < 10 ? '000' : (number < 100 ? '00' : (number < 1000 ? '0' : ''))) + number;
            }

            return (number < 10 ? '0' : '') + number;
        }
    };

    var DateFormatter_1 = DateFormatter;

    var TimeFormatter = function (timePattern, timeFormat) {
        var owner = this;

        owner.time = [];
        owner.blocks = [];
        owner.timePattern = timePattern;
        owner.timeFormat = timeFormat;
        owner.initBlocks();
    };

    TimeFormatter.prototype = {
        initBlocks: function () {
            var owner = this;
            owner.timePattern.forEach(function () {
                owner.blocks.push(2);
            });
        },

        getISOFormatTime: function () {
            var owner = this,
                time = owner.time;

            return time[2] ? (
                owner.addLeadingZero(time[0]) + ':' + owner.addLeadingZero(time[1]) + ':' + owner.addLeadingZero(time[2])
            ) : '';
        },

        getBlocks: function () {
            return this.blocks;
        },

        getTimeFormatOptions: function () {
            var owner = this;
            if (String(owner.timeFormat) === '12') {
                return {
                    maxHourFirstDigit: 1,
                    maxHours: 12,
                    maxMinutesFirstDigit: 5,
                    maxMinutes: 60
                };
            }

            return {
                maxHourFirstDigit: 2,
                maxHours: 23,
                maxMinutesFirstDigit: 5,
                maxMinutes: 60
            };
        },

        getValidatedTime: function (value) {
            var owner = this, result = '';

            value = value.replace(/[^\d]/g, '');

            var timeFormatOptions = owner.getTimeFormatOptions();

            owner.blocks.forEach(function (length, index) {
                if (value.length > 0) {
                    var sub = value.slice(0, length),
                        sub0 = sub.slice(0, 1),
                        rest = value.slice(length);

                    switch (owner.timePattern[index]) {

                        case 'h':
                            if (parseInt(sub0, 10) > timeFormatOptions.maxHourFirstDigit) {
                                sub = '0' + sub0;
                            } else if (parseInt(sub, 10) > timeFormatOptions.maxHours) {
                                sub = timeFormatOptions.maxHours + '';
                            }

                            break;

                        case 'm':
                        case 's':
                            if (parseInt(sub0, 10) > timeFormatOptions.maxMinutesFirstDigit) {
                                sub = '0' + sub0;
                            } else if (parseInt(sub, 10) > timeFormatOptions.maxMinutes) {
                                sub = timeFormatOptions.maxMinutes + '';
                            }
                            break;
                    }

                    result += sub;

                    // update remaining string
                    value = rest;
                }
            });

            return this.getFixedTimeString(result);
        },

        getFixedTimeString: function (value) {
            var owner = this, timePattern = owner.timePattern, time = [],
                secondIndex = 0, minuteIndex = 0, hourIndex = 0,
                secondStartIndex = 0, minuteStartIndex = 0, hourStartIndex = 0,
                second, minute, hour;

            if (value.length === 6) {
                timePattern.forEach(function (type, index) {
                    switch (type) {
                        case 's':
                            secondIndex = index * 2;
                            break;
                        case 'm':
                            minuteIndex = index * 2;
                            break;
                        case 'h':
                            hourIndex = index * 2;
                            break;
                    }
                });

                hourStartIndex = hourIndex;
                minuteStartIndex = minuteIndex;
                secondStartIndex = secondIndex;

                second = parseInt(value.slice(secondStartIndex, secondStartIndex + 2), 10);
                minute = parseInt(value.slice(minuteStartIndex, minuteStartIndex + 2), 10);
                hour = parseInt(value.slice(hourStartIndex, hourStartIndex + 2), 10);

                time = this.getFixedTime(hour, minute, second);
            }

            if (value.length === 4 && owner.timePattern.indexOf('s') < 0) {
                timePattern.forEach(function (type, index) {
                    switch (type) {
                        case 'm':
                            minuteIndex = index * 2;
                            break;
                        case 'h':
                            hourIndex = index * 2;
                            break;
                    }
                });

                hourStartIndex = hourIndex;
                minuteStartIndex = minuteIndex;

                second = 0;
                minute = parseInt(value.slice(minuteStartIndex, minuteStartIndex + 2), 10);
                hour = parseInt(value.slice(hourStartIndex, hourStartIndex + 2), 10);

                time = this.getFixedTime(hour, minute, second);
            }

            owner.time = time;

            return time.length === 0 ? value : timePattern.reduce(function (previous, current) {
                switch (current) {
                    case 's':
                        return previous + owner.addLeadingZero(time[2]);
                    case 'm':
                        return previous + owner.addLeadingZero(time[1]);
                    case 'h':
                        return previous + owner.addLeadingZero(time[0]);
                }
            }, '');
        },

        getFixedTime: function (hour, minute, second) {
            second = Math.min(parseInt(second || 0, 10), 60);
            minute = Math.min(minute, 60);
            hour = Math.min(hour, 60);

            return [hour, minute, second];
        },

        addLeadingZero: function (number) {
            return (number < 10 ? '0' : '') + number;
        }
    };

    var TimeFormatter_1 = TimeFormatter;

    var PhoneFormatter = function (formatter, delimiter) {
        var owner = this;

        owner.delimiter = (delimiter || delimiter === '') ? delimiter : ' ';
        owner.delimiterRE = delimiter ? new RegExp('\\' + delimiter, 'g') : '';

        owner.formatter = formatter;
    };

    PhoneFormatter.prototype = {
        setFormatter: function (formatter) {
            this.formatter = formatter;
        },

        format: function (phoneNumber) {
            var owner = this;

            owner.formatter.clear();

            // only keep number and +
            phoneNumber = phoneNumber.replace(/[^\d+]/g, '');

            // strip non-leading +
            phoneNumber = phoneNumber.replace(/^\+/, 'B').replace(/\+/g, '').replace('B', '+');

            // strip delimiter
            phoneNumber = phoneNumber.replace(owner.delimiterRE, '');

            var result = '', current, validated = false;

            for (var i = 0, iMax = phoneNumber.length; i < iMax; i++) {
                current = owner.formatter.inputDigit(phoneNumber.charAt(i));

                // has ()- or space inside
                if (/[\s()-]/g.test(current)) {
                    result = current;

                    validated = true;
                } else {
                    if (!validated) {
                        result = current;
                    }
                    // else: over length input
                    // it turns to invalid number again
                }
            }

            // strip ()
            // e.g. US: 7161234567 returns (716) 123-4567
            result = result.replace(/[()]/g, '');
            // replace library delimiter with user customized delimiter
            result = result.replace(/[\s-]/g, owner.delimiter);

            return result;
        }
    };

    var PhoneFormatter_1 = PhoneFormatter;

    var CreditCardDetector = {
        blocks: {
            uatp:          [4, 5, 6],
            amex:          [4, 6, 5],
            diners:        [4, 6, 4],
            discover:      [4, 4, 4, 4],
            mastercard:    [4, 4, 4, 4],
            dankort:       [4, 4, 4, 4],
            instapayment:  [4, 4, 4, 4],
            jcb15:         [4, 6, 5],
            jcb:           [4, 4, 4, 4],
            maestro:       [4, 4, 4, 4],
            visa:          [4, 4, 4, 4],
            mir:           [4, 4, 4, 4],
            unionPay:      [4, 4, 4, 4],
            general:       [4, 4, 4, 4]
        },

        re: {
            // starts with 1; 15 digits, not starts with 1800 (jcb card)
            uatp: /^(?!1800)1\d{0,14}/,

            // starts with 34/37; 15 digits
            amex: /^3[47]\d{0,13}/,

            // starts with 6011/65/644-649; 16 digits
            discover: /^(?:6011|65\d{0,2}|64[4-9]\d?)\d{0,12}/,

            // starts with 300-305/309 or 36/38/39; 14 digits
            diners: /^3(?:0([0-5]|9)|[689]\d?)\d{0,11}/,

            // starts with 51-55/2221–2720; 16 digits
            mastercard: /^(5[1-5]\d{0,2}|22[2-9]\d{0,1}|2[3-7]\d{0,2})\d{0,12}/,

            // starts with 5019/4175/4571; 16 digits
            dankort: /^(5019|4175|4571)\d{0,12}/,

            // starts with 637-639; 16 digits
            instapayment: /^63[7-9]\d{0,13}/,

            // starts with 2131/1800; 15 digits
            jcb15: /^(?:2131|1800)\d{0,11}/,

            // starts with 2131/1800/35; 16 digits
            jcb: /^(?:35\d{0,2})\d{0,12}/,

            // starts with 50/56-58/6304/67; 16 digits
            maestro: /^(?:5[0678]\d{0,2}|6304|67\d{0,2})\d{0,12}/,

            // starts with 22; 16 digits
            mir: /^220[0-4]\d{0,12}/,

            // starts with 4; 16 digits
            visa: /^4\d{0,15}/,

            // starts with 62/81; 16 digits
            unionPay: /^(62|81)\d{0,14}/
        },

        getStrictBlocks: function (block) {
            var total = block.reduce(function (prev, current) {
                return prev + current;
            }, 0);

            return block.concat(19 - total);
        },

        getInfo: function (value, strictMode) {
            var blocks = CreditCardDetector.blocks,
                re = CreditCardDetector.re;

            // Some credit card can have up to 19 digits number.
            // Set strictMode to true will remove the 16 max-length restrain,
            // however, I never found any website validate card number like
            // this, hence probably you don't want to enable this option.
            strictMode = !!strictMode;

            for (var key in re) {
                if (re[key].test(value)) {
                    var matchedBlocks = blocks[key];
                    return {
                        type: key,
                        blocks: strictMode ? this.getStrictBlocks(matchedBlocks) : matchedBlocks
                    };
                }
            }

            return {
                type: 'unknown',
                blocks: strictMode ? this.getStrictBlocks(blocks.general) : blocks.general
            };
        }
    };

    var CreditCardDetector_1 = CreditCardDetector;

    var Util = {
        noop: function () {
        },

        strip: function (value, re) {
            return value.replace(re, '');
        },

        getPostDelimiter: function (value, delimiter, delimiters) {
            // single delimiter
            if (delimiters.length === 0) {
                return value.slice(-delimiter.length) === delimiter ? delimiter : '';
            }

            // multiple delimiters
            var matchedDelimiter = '';
            delimiters.forEach(function (current) {
                if (value.slice(-current.length) === current) {
                    matchedDelimiter = current;
                }
            });

            return matchedDelimiter;
        },

        getDelimiterREByDelimiter: function (delimiter) {
            return new RegExp(delimiter.replace(/([.?*+^$[\]\\(){}|-])/g, '\\$1'), 'g');
        },

        getNextCursorPosition: function (prevPos, oldValue, newValue, delimiter, delimiters) {
            // If cursor was at the end of value, just place it back.
            // Because new value could contain additional chars.
            if (oldValue.length === prevPos) {
                return newValue.length;
            }

            return prevPos + this.getPositionOffset(prevPos, oldValue, newValue, delimiter ,delimiters);
        },

        getPositionOffset: function (prevPos, oldValue, newValue, delimiter, delimiters) {
            var oldRawValue, newRawValue, lengthOffset;

            oldRawValue = this.stripDelimiters(oldValue.slice(0, prevPos), delimiter, delimiters);
            newRawValue = this.stripDelimiters(newValue.slice(0, prevPos), delimiter, delimiters);
            lengthOffset = oldRawValue.length - newRawValue.length;

            return (lengthOffset !== 0) ? (lengthOffset / Math.abs(lengthOffset)) : 0;
        },

        stripDelimiters: function (value, delimiter, delimiters) {
            var owner = this;

            // single delimiter
            if (delimiters.length === 0) {
                var delimiterRE = delimiter ? owner.getDelimiterREByDelimiter(delimiter) : '';

                return value.replace(delimiterRE, '');
            }

            // multiple delimiters
            delimiters.forEach(function (current) {
                current.split('').forEach(function (letter) {
                    value = value.replace(owner.getDelimiterREByDelimiter(letter), '');
                });
            });

            return value;
        },

        headStr: function (str, length) {
            return str.slice(0, length);
        },

        getMaxLength: function (blocks) {
            return blocks.reduce(function (previous, current) {
                return previous + current;
            }, 0);
        },

        // strip prefix
        // Before type  |   After type    |     Return value
        // PEFIX-...    |   PEFIX-...     |     ''
        // PREFIX-123   |   PEFIX-123     |     123
        // PREFIX-123   |   PREFIX-23     |     23
        // PREFIX-123   |   PREFIX-1234   |     1234
        getPrefixStrippedValue: function (value, prefix, prefixLength, prevResult, delimiter, delimiters, noImmediatePrefix, tailPrefix, signBeforePrefix) {
            // No prefix
            if (prefixLength === 0) {
                return value;
            }

            // Value is prefix
            if (value === prefix && value !== '') {
                return '';
            }

            if (signBeforePrefix && (value.slice(0, 1) == '-')) {
                var prev = (prevResult.slice(0, 1) == '-') ? prevResult.slice(1) : prevResult;
                return '-' + this.getPrefixStrippedValue(value.slice(1), prefix, prefixLength, prev, delimiter, delimiters, noImmediatePrefix, tailPrefix, signBeforePrefix);
            }

            // Pre result prefix string does not match pre-defined prefix
            if (prevResult.slice(0, prefixLength) !== prefix && !tailPrefix) {
                // Check if the first time user entered something
                if (noImmediatePrefix && !prevResult && value) return value;
                return '';
            } else if (prevResult.slice(-prefixLength) !== prefix && tailPrefix) {
                // Check if the first time user entered something
                if (noImmediatePrefix && !prevResult && value) return value;
                return '';
            }

            var prevValue = this.stripDelimiters(prevResult, delimiter, delimiters);

            // New value has issue, someone typed in between prefix letters
            // Revert to pre value
            if (value.slice(0, prefixLength) !== prefix && !tailPrefix) {
                return prevValue.slice(prefixLength);
            } else if (value.slice(-prefixLength) !== prefix && tailPrefix) {
                return prevValue.slice(0, -prefixLength - 1);
            }

            // No issue, strip prefix for new value
            return tailPrefix ? value.slice(0, -prefixLength) : value.slice(prefixLength);
        },

        getFirstDiffIndex: function (prev, current) {
            var index = 0;

            while (prev.charAt(index) === current.charAt(index)) {
                if (prev.charAt(index++) === '') {
                    return -1;
                }
            }

            return index;
        },

        getFormattedValue: function (value, blocks, blocksLength, delimiter, delimiters, delimiterLazyShow) {
            var result = '',
                multipleDelimiters = delimiters.length > 0,
                currentDelimiter = '';

            // no options, normal input
            if (blocksLength === 0) {
                return value;
            }

            blocks.forEach(function (length, index) {
                if (value.length > 0) {
                    var sub = value.slice(0, length),
                        rest = value.slice(length);

                    if (multipleDelimiters) {
                        currentDelimiter = delimiters[delimiterLazyShow ? (index - 1) : index] || currentDelimiter;
                    } else {
                        currentDelimiter = delimiter;
                    }

                    if (delimiterLazyShow) {
                        if (index > 0) {
                            result += currentDelimiter;
                        }

                        result += sub;
                    } else {
                        result += sub;

                        if (sub.length === length && index < blocksLength - 1) {
                            result += currentDelimiter;
                        }
                    }

                    // update remaining string
                    value = rest;
                }
            });

            return result;
        },

        // move cursor to the end
        // the first time user focuses on an input with prefix
        fixPrefixCursor: function (el, prefix, delimiter, delimiters) {
            if (!el) {
                return;
            }

            var val = el.value,
                appendix = delimiter || (delimiters[0] || ' ');

            if (!el.setSelectionRange || !prefix || (prefix.length + appendix.length) <= val.length) {
                return;
            }

            var len = val.length * 2;

            // set timeout to avoid blink
            setTimeout(function () {
                el.setSelectionRange(len, len);
            }, 1);
        },

        // Check if input field is fully selected
        checkFullSelection: function(value) {
            try {
                var selection = window.getSelection() || document.getSelection() || {};
                return selection.toString().length === value.length;
            } catch (ex) {
                // Ignore
            }

            return false;
        },

        setSelection: function (element, position, doc) {
            if (element !== this.getActiveElement(doc)) {
                return;
            }

            // cursor is already in the end
            if (element && element.value.length <= position) {
                return;
            }

            if (element.createTextRange) {
                var range = element.createTextRange();

                range.move('character', position);
                range.select();
            } else {
                try {
                    element.setSelectionRange(position, position);
                } catch (e) {
                    // eslint-disable-next-line
                    console.warn('The input element type does not support selection');
                }
            }
        },

        getActiveElement: function(parent) {
            var activeElement = parent.activeElement;
            if (activeElement && activeElement.shadowRoot) {
                return this.getActiveElement(activeElement.shadowRoot);
            }
            return activeElement;
        },

        isAndroid: function () {
            return navigator && /android/i.test(navigator.userAgent);
        },

        // On Android chrome, the keyup and keydown events
        // always return key code 229 as a composition that
        // buffers the user’s keystrokes
        // see https://github.com/nosir/cleave.js/issues/147
        isAndroidBackspaceKeydown: function (lastInputValue, currentInputValue) {
            if (!this.isAndroid() || !lastInputValue || !currentInputValue) {
                return false;
            }

            return currentInputValue === lastInputValue.slice(0, -1);
        }
    };

    var Util_1 = Util;

    /**
     * Props Assignment
     *
     * Separate this, so react module can share the usage
     */
    var DefaultProperties = {
        // Maybe change to object-assign
        // for now just keep it as simple
        assign: function (target, opts) {
            target = target || {};
            opts = opts || {};

            // credit card
            target.creditCard = !!opts.creditCard;
            target.creditCardStrictMode = !!opts.creditCardStrictMode;
            target.creditCardType = '';
            target.onCreditCardTypeChanged = opts.onCreditCardTypeChanged || (function () {});

            // phone
            target.phone = !!opts.phone;
            target.phoneRegionCode = opts.phoneRegionCode || 'AU';
            target.phoneFormatter = {};

            // time
            target.time = !!opts.time;
            target.timePattern = opts.timePattern || ['h', 'm', 's'];
            target.timeFormat = opts.timeFormat || '24';
            target.timeFormatter = {};

            // date
            target.date = !!opts.date;
            target.datePattern = opts.datePattern || ['d', 'm', 'Y'];
            target.dateMin = opts.dateMin || '';
            target.dateMax = opts.dateMax || '';
            target.dateFormatter = {};

            // numeral
            target.numeral = !!opts.numeral;
            target.numeralIntegerScale = opts.numeralIntegerScale > 0 ? opts.numeralIntegerScale : 0;
            target.numeralDecimalScale = opts.numeralDecimalScale >= 0 ? opts.numeralDecimalScale : 2;
            target.numeralDecimalMark = opts.numeralDecimalMark || '.';
            target.numeralThousandsGroupStyle = opts.numeralThousandsGroupStyle || 'thousand';
            target.numeralPositiveOnly = !!opts.numeralPositiveOnly;
            target.stripLeadingZeroes = opts.stripLeadingZeroes !== false;
            target.signBeforePrefix = !!opts.signBeforePrefix;
            target.tailPrefix = !!opts.tailPrefix;

            // others
            target.swapHiddenInput = !!opts.swapHiddenInput;

            target.numericOnly = target.creditCard || target.date || !!opts.numericOnly;

            target.uppercase = !!opts.uppercase;
            target.lowercase = !!opts.lowercase;

            target.prefix = (target.creditCard || target.date) ? '' : (opts.prefix || '');
            target.noImmediatePrefix = !!opts.noImmediatePrefix;
            target.prefixLength = target.prefix.length;
            target.rawValueTrimPrefix = !!opts.rawValueTrimPrefix;
            target.copyDelimiter = !!opts.copyDelimiter;

            target.initValue = (opts.initValue !== undefined && opts.initValue !== null) ? opts.initValue.toString() : '';

            target.delimiter =
                (opts.delimiter || opts.delimiter === '') ? opts.delimiter :
                    (opts.date ? '/' :
                        (opts.time ? ':' :
                            (opts.numeral ? ',' :
                                (opts.phone ? ' ' :
                                    ' '))));
            target.delimiterLength = target.delimiter.length;
            target.delimiterLazyShow = !!opts.delimiterLazyShow;
            target.delimiters = opts.delimiters || [];

            target.blocks = opts.blocks || [];
            target.blocksLength = target.blocks.length;

            target.root = (typeof commonjsGlobal$1 === 'object' && commonjsGlobal$1) ? commonjsGlobal$1 : window;
            target.document = opts.document || target.root.document;

            target.maxLength = 0;

            target.backspace = false;
            target.result = '';

            target.onValueChanged = opts.onValueChanged || (function () {});

            return target;
        }
    };

    var DefaultProperties_1 = DefaultProperties;

    /**
     * Construct a new Cleave instance by passing the configuration object
     *
     * @param {String | HTMLElement} element
     * @param {Object} opts
     */
    var Cleave = function (element, opts) {
        var owner = this;
        var hasMultipleElements = false;

        if (typeof element === 'string') {
            owner.element = document.querySelector(element);
            hasMultipleElements = document.querySelectorAll(element).length > 1;
        } else {
            if (typeof element.length !== 'undefined' && element.length > 0) {
                owner.element = element[0];
                hasMultipleElements = element.length > 1;
            } else {
                owner.element = element;
            }
        }

        if (!owner.element) {
            throw new Error('[cleave.js] Please check the element');
        }

        if (hasMultipleElements) {
            try {
                // eslint-disable-next-line
                console.warn('[cleave.js] Multiple input fields matched, cleave.js will only take the first one.');
            } catch (e) {
                // Old IE
            }
        }

        opts.initValue = owner.element.value;

        owner.properties = Cleave.DefaultProperties.assign({}, opts);

        owner.init();
    };

    Cleave.prototype = {
        init: function () {
            var owner = this, pps = owner.properties;

            // no need to use this lib
            if (!pps.numeral && !pps.phone && !pps.creditCard && !pps.time && !pps.date && (pps.blocksLength === 0 && !pps.prefix)) {
                owner.onInput(pps.initValue);

                return;
            }

            pps.maxLength = Cleave.Util.getMaxLength(pps.blocks);

            owner.isAndroid = Cleave.Util.isAndroid();
            owner.lastInputValue = '';
            owner.isBackward = '';

            owner.onChangeListener = owner.onChange.bind(owner);
            owner.onKeyDownListener = owner.onKeyDown.bind(owner);
            owner.onFocusListener = owner.onFocus.bind(owner);
            owner.onCutListener = owner.onCut.bind(owner);
            owner.onCopyListener = owner.onCopy.bind(owner);

            owner.initSwapHiddenInput();

            owner.element.addEventListener('input', owner.onChangeListener);
            owner.element.addEventListener('keydown', owner.onKeyDownListener);
            owner.element.addEventListener('focus', owner.onFocusListener);
            owner.element.addEventListener('cut', owner.onCutListener);
            owner.element.addEventListener('copy', owner.onCopyListener);


            owner.initPhoneFormatter();
            owner.initDateFormatter();
            owner.initTimeFormatter();
            owner.initNumeralFormatter();

            // avoid touch input field if value is null
            // otherwise Firefox will add red box-shadow for <input required />
            if (pps.initValue || (pps.prefix && !pps.noImmediatePrefix)) {
                owner.onInput(pps.initValue);
            }
        },

        initSwapHiddenInput: function () {
            var owner = this, pps = owner.properties;
            if (!pps.swapHiddenInput) return;

            var inputFormatter = owner.element.cloneNode(true);
            owner.element.parentNode.insertBefore(inputFormatter, owner.element);

            owner.elementSwapHidden = owner.element;
            owner.elementSwapHidden.type = 'hidden';

            owner.element = inputFormatter;
            owner.element.id = '';
        },

        initNumeralFormatter: function () {
            var owner = this, pps = owner.properties;

            if (!pps.numeral) {
                return;
            }

            pps.numeralFormatter = new Cleave.NumeralFormatter(
                pps.numeralDecimalMark,
                pps.numeralIntegerScale,
                pps.numeralDecimalScale,
                pps.numeralThousandsGroupStyle,
                pps.numeralPositiveOnly,
                pps.stripLeadingZeroes,
                pps.prefix,
                pps.signBeforePrefix,
                pps.tailPrefix,
                pps.delimiter
            );
        },

        initTimeFormatter: function() {
            var owner = this, pps = owner.properties;

            if (!pps.time) {
                return;
            }

            pps.timeFormatter = new Cleave.TimeFormatter(pps.timePattern, pps.timeFormat);
            pps.blocks = pps.timeFormatter.getBlocks();
            pps.blocksLength = pps.blocks.length;
            pps.maxLength = Cleave.Util.getMaxLength(pps.blocks);
        },

        initDateFormatter: function () {
            var owner = this, pps = owner.properties;

            if (!pps.date) {
                return;
            }

            pps.dateFormatter = new Cleave.DateFormatter(pps.datePattern, pps.dateMin, pps.dateMax);
            pps.blocks = pps.dateFormatter.getBlocks();
            pps.blocksLength = pps.blocks.length;
            pps.maxLength = Cleave.Util.getMaxLength(pps.blocks);
        },

        initPhoneFormatter: function () {
            var owner = this, pps = owner.properties;

            if (!pps.phone) {
                return;
            }

            // Cleave.AsYouTypeFormatter should be provided by
            // external google closure lib
            try {
                pps.phoneFormatter = new Cleave.PhoneFormatter(
                    new pps.root.Cleave.AsYouTypeFormatter(pps.phoneRegionCode),
                    pps.delimiter
                );
            } catch (ex) {
                throw new Error('[cleave.js] Please include phone-type-formatter.{country}.js lib');
            }
        },

        onKeyDown: function (event) {
            var owner = this,
                charCode = event.which || event.keyCode;

            owner.lastInputValue = owner.element.value;
            owner.isBackward = charCode === 8;
        },

        onChange: function (event) {
            var owner = this, pps = owner.properties,
                Util = Cleave.Util;

            owner.isBackward = owner.isBackward || event.inputType === 'deleteContentBackward';

            var postDelimiter = Util.getPostDelimiter(owner.lastInputValue, pps.delimiter, pps.delimiters);

            if (owner.isBackward && postDelimiter) {
                pps.postDelimiterBackspace = postDelimiter;
            } else {
                pps.postDelimiterBackspace = false;
            }

            this.onInput(this.element.value);
        },

        onFocus: function () {
            var owner = this,
                pps = owner.properties;
            owner.lastInputValue = owner.element.value;

            if (pps.prefix && pps.noImmediatePrefix && !owner.element.value) {
                this.onInput(pps.prefix);
            }

            Cleave.Util.fixPrefixCursor(owner.element, pps.prefix, pps.delimiter, pps.delimiters);
        },

        onCut: function (e) {
            if (!Cleave.Util.checkFullSelection(this.element.value)) return;
            this.copyClipboardData(e);
            this.onInput('');
        },

        onCopy: function (e) {
            if (!Cleave.Util.checkFullSelection(this.element.value)) return;
            this.copyClipboardData(e);
        },

        copyClipboardData: function (e) {
            var owner = this,
                pps = owner.properties,
                Util = Cleave.Util,
                inputValue = owner.element.value,
                textToCopy = '';

            if (!pps.copyDelimiter) {
                textToCopy = Util.stripDelimiters(inputValue, pps.delimiter, pps.delimiters);
            } else {
                textToCopy = inputValue;
            }

            try {
                if (e.clipboardData) {
                    e.clipboardData.setData('Text', textToCopy);
                } else {
                    window.clipboardData.setData('Text', textToCopy);
                }

                e.preventDefault();
            } catch (ex) {
                //  empty
            }
        },

        onInput: function (value) {
            var owner = this, pps = owner.properties,
                Util = Cleave.Util;

            // case 1: delete one more character "4"
            // 1234*| -> hit backspace -> 123|
            // case 2: last character is not delimiter which is:
            // 12|34* -> hit backspace -> 1|34*
            // note: no need to apply this for numeral mode
            var postDelimiterAfter = Util.getPostDelimiter(value, pps.delimiter, pps.delimiters);
            if (!pps.numeral && pps.postDelimiterBackspace && !postDelimiterAfter) {
                value = Util.headStr(value, value.length - pps.postDelimiterBackspace.length);
            }

            // phone formatter
            if (pps.phone) {
                if (pps.prefix && (!pps.noImmediatePrefix || value.length)) {
                    pps.result = pps.prefix + pps.phoneFormatter.format(value).slice(pps.prefix.length);
                } else {
                    pps.result = pps.phoneFormatter.format(value);
                }
                owner.updateValueState();

                return;
            }

            // numeral formatter
            if (pps.numeral) {
                // Do not show prefix when noImmediatePrefix is specified
                // This mostly because we need to show user the native input placeholder
                if (pps.prefix && pps.noImmediatePrefix && value.length === 0) {
                    pps.result = '';
                } else {
                    pps.result = pps.numeralFormatter.format(value);
                }
                owner.updateValueState();

                return;
            }

            // date
            if (pps.date) {
                value = pps.dateFormatter.getValidatedDate(value);
            }

            // time
            if (pps.time) {
                value = pps.timeFormatter.getValidatedTime(value);
            }

            // strip delimiters
            value = Util.stripDelimiters(value, pps.delimiter, pps.delimiters);

            // strip prefix
            value = Util.getPrefixStrippedValue(value, pps.prefix, pps.prefixLength, pps.result, pps.delimiter, pps.delimiters, pps.noImmediatePrefix, pps.tailPrefix, pps.signBeforePrefix);

            // strip non-numeric characters
            value = pps.numericOnly ? Util.strip(value, /[^\d]/g) : value;

            // convert case
            value = pps.uppercase ? value.toUpperCase() : value;
            value = pps.lowercase ? value.toLowerCase() : value;

            // prevent from showing prefix when no immediate option enabled with empty input value
            if (pps.prefix) {
                if (pps.tailPrefix) {
                    value = value + pps.prefix;
                } else {
                    value = pps.prefix + value;
                }


                // no blocks specified, no need to do formatting
                if (pps.blocksLength === 0) {
                    pps.result = value;
                    owner.updateValueState();

                    return;
                }
            }

            // update credit card props
            if (pps.creditCard) {
                owner.updateCreditCardPropsByValue(value);
            }

            // strip over length characters
            value = Util.headStr(value, pps.maxLength);

            // apply blocks
            pps.result = Util.getFormattedValue(
                value,
                pps.blocks, pps.blocksLength,
                pps.delimiter, pps.delimiters, pps.delimiterLazyShow
            );

            owner.updateValueState();
        },

        updateCreditCardPropsByValue: function (value) {
            var owner = this, pps = owner.properties,
                Util = Cleave.Util,
                creditCardInfo;

            // At least one of the first 4 characters has changed
            if (Util.headStr(pps.result, 4) === Util.headStr(value, 4)) {
                return;
            }

            creditCardInfo = Cleave.CreditCardDetector.getInfo(value, pps.creditCardStrictMode);

            pps.blocks = creditCardInfo.blocks;
            pps.blocksLength = pps.blocks.length;
            pps.maxLength = Util.getMaxLength(pps.blocks);

            // credit card type changed
            if (pps.creditCardType !== creditCardInfo.type) {
                pps.creditCardType = creditCardInfo.type;

                pps.onCreditCardTypeChanged.call(owner, pps.creditCardType);
            }
        },

        updateValueState: function () {
            var owner = this,
                Util = Cleave.Util,
                pps = owner.properties;

            if (!owner.element) {
                return;
            }

            var endPos = owner.element.selectionEnd;
            var oldValue = owner.element.value;
            var newValue = pps.result;

            endPos = Util.getNextCursorPosition(endPos, oldValue, newValue, pps.delimiter, pps.delimiters);

            // fix Android browser type="text" input field
            // cursor not jumping issue
            if (owner.isAndroid) {
                window.setTimeout(function () {
                    owner.element.value = newValue;
                    Util.setSelection(owner.element, endPos, pps.document, false);
                    owner.callOnValueChanged();
                }, 1);

                return;
            }

            owner.element.value = newValue;
            if (pps.swapHiddenInput) owner.elementSwapHidden.value = owner.getRawValue();

            Util.setSelection(owner.element, endPos, pps.document, false);
            owner.callOnValueChanged();
        },

        callOnValueChanged: function () {
            var owner = this,
                pps = owner.properties;

            pps.onValueChanged.call(owner, {
                target: {
                    name: owner.element.name,
                    value: pps.result,
                    rawValue: owner.getRawValue()
                }
            });
        },

        setPhoneRegionCode: function (phoneRegionCode) {
            var owner = this, pps = owner.properties;

            pps.phoneRegionCode = phoneRegionCode;
            owner.initPhoneFormatter();
            owner.onChange();
        },

        setRawValue: function (value) {
            var owner = this, pps = owner.properties;

            value = value !== undefined && value !== null ? value.toString() : '';

            if (pps.numeral) {
                value = value.replace('.', pps.numeralDecimalMark);
            }

            pps.postDelimiterBackspace = false;

            owner.element.value = value;
            owner.onInput(value);
        },

        getRawValue: function () {
            var owner = this,
                pps = owner.properties,
                Util = Cleave.Util,
                rawValue = owner.element.value;

            if (pps.rawValueTrimPrefix) {
                rawValue = Util.getPrefixStrippedValue(rawValue, pps.prefix, pps.prefixLength, pps.result, pps.delimiter, pps.delimiters, pps.noImmediatePrefix, pps.tailPrefix, pps.signBeforePrefix);
            }

            if (pps.numeral) {
                rawValue = pps.numeralFormatter.getRawValue(rawValue);
            } else {
                rawValue = Util.stripDelimiters(rawValue, pps.delimiter, pps.delimiters);
            }

            return rawValue;
        },

        getISOFormatDate: function () {
            var owner = this,
                pps = owner.properties;

            return pps.date ? pps.dateFormatter.getISOFormatDate() : '';
        },

        getISOFormatTime: function () {
            var owner = this,
                pps = owner.properties;

            return pps.time ? pps.timeFormatter.getISOFormatTime() : '';
        },

        getFormattedValue: function () {
            return this.element.value;
        },

        destroy: function () {
            var owner = this;

            owner.element.removeEventListener('input', owner.onChangeListener);
            owner.element.removeEventListener('keydown', owner.onKeyDownListener);
            owner.element.removeEventListener('focus', owner.onFocusListener);
            owner.element.removeEventListener('cut', owner.onCutListener);
            owner.element.removeEventListener('copy', owner.onCopyListener);
        },

        toString: function () {
            return '[Cleave Object]';
        }
    };

    Cleave.NumeralFormatter = NumeralFormatter_1;
    Cleave.DateFormatter = DateFormatter_1;
    Cleave.TimeFormatter = TimeFormatter_1;
    Cleave.PhoneFormatter = PhoneFormatter_1;
    Cleave.CreditCardDetector = CreditCardDetector_1;
    Cleave.Util = Util_1;
    Cleave.DefaultProperties = DefaultProperties_1;

    // for angular directive
    ((typeof commonjsGlobal$1 === 'object' && commonjsGlobal$1) ? commonjsGlobal$1 : window)['Cleave'] = Cleave;

    // CommonJS
    var Cleave_1 = Cleave;

    class DebugEvent extends GenericEvent {}

    class DebugListener extends EventListener {
        /**
         * Actual debug information to pass to the console.
         *
         * @param event
         */
        static onDebugEvent(event)
        {
            if (event.getSubject() instanceof Error) {
                throw event.getSubject();
            }

            if (event.getParameters().length > 0) {
                if (event instanceof DebugEvent) {
                    console.debug(event.event, event.event.getParameters());
                } else {
                    console.debug(event.getSubject(), event.getParameters());
                }
            } else {
                console.debug(event.getSubject());
            }
        }

        static onDebugNull() {}

        /**
         * @param {ModalCloseEvent} event
         */
        static onModalCloseEvent(event)
        {
            EventDispatcher.getInstance().dispatch(new DebugEvent('Closing modal', {
                'event': event,
            }), Events.onDebugEvent);
        }

        /**
         * @param {ModalOpenEvent} event
         */
        static onModalOpenEvent(event)
        {
            EventDispatcher.getInstance().dispatch(new DebugEvent('Opening modal', {
                'event': event,
            }), Events.onDebugEvent);
        }

        /**
         * @param {RequestEvent} event
         */
        static onRequestEvent(event)
        {
            EventDispatcher.getInstance().dispatch(new DebugEvent('Request received.', {
                'event': event,
            }), Events.onDebugEvent);
        }

        /**
         * @param {ResponseEvent} event
         */
        static onResponseEvent(event)
        {
            EventDispatcher.getInstance().dispatch(new DebugEvent('Response received', {
                'event': event,
            }), Events.onDebugEvent);
        }

        /**
         * @param {ResponseFactoryEvent} event
         */
        static onResponseFactoryEvent(event)
        {
            EventDispatcher.getInstance().dispatch(new DebugEvent('Response factory has resolved response', {
                'event': event,
            }), Events.onDebugEvent);
        }

        /**
         * @param {StateChangeEvent} event
         */
        static onStateChangeEvent(event)
        {
            EventDispatcher.getInstance().dispatch(new DebugEvent('State has been updated', {
                'event': event,
                'state': event.getCurrentState(),
                'change': event.parameters
            }), Events.onDebugEvent);
        }

        /**
         * @param {PaymentCompleteEvent} event
         */
        static onPaymentCompleteEvent(event)
        {
            EventDispatcher.getInstance().dispatch(new DebugEvent('Payment was successfully completed.', {
                'event': event,
            }), Events.onDebugEvent);
        }

        /**
         * @param {PaymentCompleteEvent} event
         */
        static onPaymentFailedEvent(event)
        {
            EventDispatcher.getInstance().dispatch(new DebugEvent('Payment has failed.', {
                'event': event,
            }), Events.onDebugEvent);
        }
    }

    class ErrorModal extends Modal {
        // eslint-disable-next-line
        render(parameters = {}) {
            let errorMessage = this.response.getMessage();

            if (typeof errorMessage === 'undefined') {
                errorMessage = 'Unknown error occurred.';
            }

            return `${errorMessage}`;
        }
    }

    class InvalidEventException extends Exception {}

    class FormDisableElementsListener {
        /**
         * @param {StateChangeEvent} event
         */
        static onStateChangeEvent(event) {
            if (!(event instanceof StateChangeEvent)) {
                throw new InvalidEventException('PaymentFormListener only supports StateChangeEvent.');
            }

            let state = State.getInstance();

            FormDisableElementsListener.formElementsDisable(state);
        }

        /**
         * Track when the state allows the form elements to be disabled.
         *
         * @param state
         */
        static formElementsDisable(state)
        {
            //console.log('HIER - formElementsDisable');

            let elements = [
                state.getElementFromReference(Events$1.creditCardHolder),
                state.getElementFromReference(Events$1.creditCardNumber),
                state.getElementFromReference(Events$1.creditCardCvv),
                state.getElementFromReference(Events$1.creditCardExpiration),
                state.getElementFromReference(Events$1.creditCardExpirationMonth),
                state.getElementFromReference(Events$1.creditCardExpirationYear),
            ];

            if (state.isFormSubmitted())
            {
//                console.log('HIER - disabling');
            } else {
                //console.log('HIER - enabling');
            }

            for (const key in Object.keys(elements))
            {
                let element = elements[key];

                if (!element) {
                    continue;
                }

                if (state.isFormSubmitted())
                {
                    if (!(element.hasAttribute('disabled'))) {
                        element.setAttribute('disabled', 'disabled');
                    }

                    if (element.hasAttribute('required')) {
                        element.removeAttribute('required');
                    }
                } else
                {
                    if (element.hasAttribute('disabled')) {
                        element.removeAttribute('disabled');
                    }

                    if (!(element.hasAttribute('required'))) {
                        element.setAttribute('required', 'required');
                    }
                }
            }
        }
    }

    class FormSubmissionListener {
        /**
         * @param {StateChangeEvent} event
         */
        static onStateChangeEvent(event) {
            if (!(event instanceof StateChangeEvent)) {
                throw new InvalidEventException('PaymentFormListener only supports StateChangeEvent.');
            }

            let state = State.getInstance();

            FormSubmissionListener.formSubmission(state);
        }

        /**
         * Track when the state allows the form to be submitted.
         *
         * @param state
         */
        static formSubmission(state)
        {
            let submitButton = state.getElementFromReference(Events$1.submitButton);

            submitButton.setAttribute('data-loading-state', state.isLoading() || state.isFormSubmitted() ? '1' : '0');

            if (state.isFormReadyForSubmission()) {
                submitButton.removeAttribute('disabled');
            } else {
                if (!(submitButton.hasAttribute('disabled'))) {
                    submitButton.setAttribute('disabled', 'disabled');
                }
            }
        }
    }

    class PublicKey {
        /**
         * Create a new public key instance.
         *
         * @param identifier
         * @param key
         * @param expiresAt
         */
        constructor(identifier, key, expiresAt) {
            this.identifier = identifier;
            this.key = atob(key);
            this.expiresAt = expiresAt;
        }

        /**
         * Return the public key.
         *
         * @returns {string}
         */
        getKey() {
            return this.key;
        }

        /**
         * Verify the public key has not expired.
         *
         * @returns {boolean}
         */
        isValid() {
            return !this.isExpired();
        }

        /**
         * Verify the public key has not expired.
         *
         * @returns {boolean}
         */
        isExpired() {
            return new Date() > this.expiresAt;
        }
    }

    /**
     * @typedef {Object} JsonPublicKeysResponse
     * @property {String} identifier
     * @property {String} public_key
     * @property {String} expires_at
     */
    class PublicKeyFactory {
        /**
         * Create a new public key object.
         *
         * @param identifier
         * @param publicKey
         * @param expiresAt
         * @returns {PublicKey}
         */
        static create(identifier, publicKey, expiresAt) {
            return new PublicKey(
                identifier,
                publicKey,
                expiresAt
            );
        }

        /**
         * Create a collection of keys from JSON.
         *
         * @param data {[JsonPublicKeysResponse]}
         * @returns {[PublicKey]}
         */
        static fromJson(data) {
            let publicKeys = [];
            for (let key of data || []) {
                publicKeys.push(
                    this.create(
                        key.identifier,
                        key.public_key,
                        key.expires_at
                    )
                );
            }

            return publicKeys;
        }
    }

    class KeyManager {
        /**
         * Construct the key manager.
         *
         * @param publicKeys
         * @param refreshUrl
         */
        constructor(publicKeys, refreshUrl) {
            this.publicKeys = publicKeys;
            this.refreshUrl = refreshUrl;
        }

        /**
         * Return a promise to return a non expired public key.
         *
         * @returns {Promise<PublicKey>}
         */
        getUnexpiredPublicKey() {
            return new Promise((resolve, reject) => {
                return this.all().then((publicKeys) => {
                    if ('undefined' !== typeof publicKeys && publicKeys.length > 0) {
                        resolve(publicKeys[0]);
                    }

                    reject(Error('Unable to fetch keys'));
                }).catch((error) => {
                    throw error;
                });
            });
        }

        /**
         * If keys were passed whilst constructing the PayEncryptedForm object, and valid keys are present, these will be returned instantly.
         * Otherwise, keys are going to be fetched from the refresh url.
         *
         * @returns {Promise<PublicKey[]>}
         */
        all() {
            return new Promise((resolve, reject) => {
                let publicKeys = this.publicKeys.filter(function (publicKey) {
                    return publicKey.isValid();
                });

                if (publicKeys.length > 0) {
                    resolve(publicKeys);
                    return;
                }

                return this.refreshPublicKeys().then((publicKeys) => {
                    this.publicKeys = publicKeys;
                    resolve(publicKeys);
                }).catch((error) => {
                    reject(error);
                });
            });
        }

        /**
         * Refresh the public keys from remote location.
         *
         * @returns {Promise<PublicKey[]>}
         */
        refreshPublicKeys() {
            return Promise.race([
                fetch(this.refreshUrl)
                    .then((response, reject) => {
                        if (response.ok) {
                            return response;
                        }

                        reject(Error('Unexpected response from server.'));
                    })
                    .then((response) => {
                        return response.json().catch(() => {
                            throw new Error('Invalid JSON returned.');
                        });
                    })
                    .then((json) => {
                        return PublicKeyFactory.fromJson(json);
                    }).catch(() => {
                    return [];
                })
                ,
                new Promise((_, reject) =>
                    setTimeout(() => reject(new Error('timeout')), 3000)
                )
            ]);
        }
    }

    function _classCallCheck(instance, Constructor) {
        if (!(instance instanceof Constructor)) {
            throw new TypeError("Cannot call a class as a function");
        }
    }

    function _defineProperties(target, props) {
        for (var i = 0; i < props.length; i++) {
            var descriptor = props[i];
            descriptor.enumerable = descriptor.enumerable || false;
            descriptor.configurable = true;
            if ("value" in descriptor) descriptor.writable = true;
            Object.defineProperty(target, descriptor.key, descriptor);
        }
    }

    function _createClass(Constructor, protoProps, staticProps) {
        if (protoProps) _defineProperties(Constructor.prototype, protoProps);
        if (staticProps) _defineProperties(Constructor, staticProps);
        return Constructor;
    }

    function _toConsumableArray(arr) {
        return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread();
    }

    function _arrayWithoutHoles(arr) {
        if (Array.isArray(arr)) return _arrayLikeToArray(arr);
    }

    function _iterableToArray(iter) {
        if (typeof Symbol !== "undefined" && Symbol.iterator in Object(iter)) return Array.from(iter);
    }

    function _unsupportedIterableToArray(o, minLen) {
        if (!o) return;
        if (typeof o === "string") return _arrayLikeToArray(o, minLen);
        var n = Object.prototype.toString.call(o).slice(8, -1);
        if (n === "Object" && o.constructor) n = o.constructor.name;
        if (n === "Map" || n === "Set") return Array.from(n);
        if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
    }

    function _arrayLikeToArray(arr, len) {
        if (len == null || len > arr.length) len = arr.length;

        for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];

        return arr2;
    }

    function _nonIterableSpread() {
        throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
    }

    var MicroModal = function () {

        var FOCUSABLE_ELEMENTS = ['a[href]', 'area[href]', 'input:not([disabled]):not([type="hidden"]):not([aria-hidden])', 'select:not([disabled]):not([aria-hidden])', 'textarea:not([disabled]):not([aria-hidden])', 'button:not([disabled]):not([aria-hidden])', 'iframe', 'object', 'embed', '[contenteditable]', '[tabindex]:not([tabindex^="-"])'];

        var Modal = /*#__PURE__*/function () {
            function Modal(_ref) {
                var targetModal = _ref.targetModal,
                    _ref$triggers = _ref.triggers,
                    triggers = _ref$triggers === void 0 ? [] : _ref$triggers,
                    _ref$onShow = _ref.onShow,
                    onShow = _ref$onShow === void 0 ? function () {} : _ref$onShow,
                    _ref$onClose = _ref.onClose,
                    onClose = _ref$onClose === void 0 ? function () {} : _ref$onClose,
                    _ref$openTrigger = _ref.openTrigger,
                    openTrigger = _ref$openTrigger === void 0 ? 'data-micromodal-trigger' : _ref$openTrigger,
                    _ref$closeTrigger = _ref.closeTrigger,
                    closeTrigger = _ref$closeTrigger === void 0 ? 'data-micromodal-close' : _ref$closeTrigger,
                    _ref$openClass = _ref.openClass,
                    openClass = _ref$openClass === void 0 ? 'is-open' : _ref$openClass,
                    _ref$disableScroll = _ref.disableScroll,
                    disableScroll = _ref$disableScroll === void 0 ? false : _ref$disableScroll,
                    _ref$disableFocus = _ref.disableFocus,
                    disableFocus = _ref$disableFocus === void 0 ? false : _ref$disableFocus,
                    _ref$awaitCloseAnimat = _ref.awaitCloseAnimation,
                    awaitCloseAnimation = _ref$awaitCloseAnimat === void 0 ? false : _ref$awaitCloseAnimat,
                    _ref$awaitOpenAnimati = _ref.awaitOpenAnimation,
                    awaitOpenAnimation = _ref$awaitOpenAnimati === void 0 ? false : _ref$awaitOpenAnimati,
                    _ref$debugMode = _ref.debugMode,
                    debugMode = _ref$debugMode === void 0 ? false : _ref$debugMode;

                _classCallCheck(this, Modal);

                // Save a reference of the modal
                this.modal = document.getElementById(targetModal); // Save a reference to the passed config

                this.config = {
                    debugMode: debugMode,
                    disableScroll: disableScroll,
                    openTrigger: openTrigger,
                    closeTrigger: closeTrigger,
                    openClass: openClass,
                    onShow: onShow,
                    onClose: onClose,
                    awaitCloseAnimation: awaitCloseAnimation,
                    awaitOpenAnimation: awaitOpenAnimation,
                    disableFocus: disableFocus
                }; // Register click events only if pre binding eventListeners

                if (triggers.length > 0) this.registerTriggers.apply(this, _toConsumableArray(triggers)); // pre bind functions for event listeners

                this.onClick = this.onClick.bind(this);
                this.onKeydown = this.onKeydown.bind(this);
            }
            /**
             * Loops through all openTriggers and binds click event
             * @param  {array} triggers [Array of node elements]
             * @return {void}
             */


            _createClass(Modal, [{
                key: "registerTriggers",
                value: function registerTriggers() {
                    var _this = this;

                    for (var _len = arguments.length, triggers = new Array(_len), _key = 0; _key < _len; _key++) {
                        triggers[_key] = arguments[_key];
                    }

                    triggers.filter(Boolean).forEach(function (trigger) {
                        trigger.addEventListener('click', function (event) {
                            return _this.showModal(event);
                        });
                    });
                }
            }, {
                key: "showModal",
                value: function showModal() {
                    var _this2 = this;

                    var event = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
                    this.activeElement = document.activeElement;
                    this.modal.setAttribute('aria-hidden', 'false');
                    this.modal.classList.add(this.config.openClass);
                    this.scrollBehaviour('disable');
                    this.addEventListeners();

                    if (this.config.awaitOpenAnimation) {
                        var handler = function handler() {
                            _this2.modal.removeEventListener('animationend', handler, false);

                            _this2.setFocusToFirstNode();
                        };

                        this.modal.addEventListener('animationend', handler, false);
                    } else {
                        this.setFocusToFirstNode();
                    }

                    this.config.onShow(this.modal, this.activeElement, event);
                }
            }, {
                key: "closeModal",
                value: function closeModal() {
                    var event = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
                    var modal = this.modal;
                    this.modal.setAttribute('aria-hidden', 'true');
                    this.removeEventListeners();
                    this.scrollBehaviour('enable');

                    if (this.activeElement && this.activeElement.focus) {
                        this.activeElement.focus();
                    }

                    this.config.onClose(this.modal, this.activeElement, event);

                    if (this.config.awaitCloseAnimation) {
                        var openClass = this.config.openClass; // <- old school ftw

                        this.modal.addEventListener('animationend', function handler() {
                            modal.classList.remove(openClass);
                            modal.removeEventListener('animationend', handler, false);
                        }, false);
                    } else {
                        modal.classList.remove(this.config.openClass);
                    }
                }
            }, {
                key: "closeModalById",
                value: function closeModalById(targetModal) {
                    this.modal = document.getElementById(targetModal);
                    if (this.modal) this.closeModal();
                }
            }, {
                key: "scrollBehaviour",
                value: function scrollBehaviour(toggle) {
                    if (!this.config.disableScroll) return;
                    var body = document.querySelector('body');

                    switch (toggle) {
                        case 'enable':
                            Object.assign(body.style, {
                                overflow: ''
                            });
                            break;

                        case 'disable':
                            Object.assign(body.style, {
                                overflow: 'hidden'
                            });
                            break;
                    }
                }
            }, {
                key: "addEventListeners",
                value: function addEventListeners() {
                    this.modal.addEventListener('touchstart', this.onClick);
                    this.modal.addEventListener('click', this.onClick);
                    document.addEventListener('keydown', this.onKeydown);
                }
            }, {
                key: "removeEventListeners",
                value: function removeEventListeners() {
                    this.modal.removeEventListener('touchstart', this.onClick);
                    this.modal.removeEventListener('click', this.onClick);
                    document.removeEventListener('keydown', this.onKeydown);
                }
            }, {
                key: "onClick",
                value: function onClick(event) {
                    if (event.target.hasAttribute(this.config.closeTrigger)) {
                        this.closeModal(event);
                    }
                }
            }, {
                key: "onKeydown",
                value: function onKeydown(event) {
                    if (event.keyCode === 27) this.closeModal(event); // esc

                    if (event.keyCode === 9) this.retainFocus(event); // tab
                }
            }, {
                key: "getFocusableNodes",
                value: function getFocusableNodes() {
                    var nodes = this.modal.querySelectorAll(FOCUSABLE_ELEMENTS);
                    return Array.apply(void 0, _toConsumableArray(nodes));
                }
                /**
                 * Tries to set focus on a node which is not a close trigger
                 * if no other nodes exist then focuses on first close trigger
                 */

            }, {
                key: "setFocusToFirstNode",
                value: function setFocusToFirstNode() {
                    var _this3 = this;

                    if (this.config.disableFocus) return;
                    var focusableNodes = this.getFocusableNodes(); // no focusable nodes

                    if (focusableNodes.length === 0) return; // remove nodes on whose click, the modal closes
                    // could not think of a better name :(

                    var nodesWhichAreNotCloseTargets = focusableNodes.filter(function (node) {
                        return !node.hasAttribute(_this3.config.closeTrigger);
                    });
                    if (nodesWhichAreNotCloseTargets.length > 0) nodesWhichAreNotCloseTargets[0].focus();
                    if (nodesWhichAreNotCloseTargets.length === 0) focusableNodes[0].focus();
                }
            }, {
                key: "retainFocus",
                value: function retainFocus(event) {
                    var focusableNodes = this.getFocusableNodes(); // no focusable nodes

                    if (focusableNodes.length === 0) return;
                    /**
                     * Filters nodes which are hidden to prevent
                     * focus leak outside modal
                     */

                    focusableNodes = focusableNodes.filter(function (node) {
                        return node.offsetParent !== null;
                    }); // if disableFocus is true

                    if (!this.modal.contains(document.activeElement)) {
                        focusableNodes[0].focus();
                    } else {
                        var focusedItemIndex = focusableNodes.indexOf(document.activeElement);

                        if (event.shiftKey && focusedItemIndex === 0) {
                            focusableNodes[focusableNodes.length - 1].focus();
                            event.preventDefault();
                        }

                        if (!event.shiftKey && focusableNodes.length > 0 && focusedItemIndex === focusableNodes.length - 1) {
                            focusableNodes[0].focus();
                            event.preventDefault();
                        }
                    }
                }
            }]);

            return Modal;
        }();
        /**
         * Modal prototype ends.
         * Here on code is responsible for detecting and
         * auto binding event handlers on modal triggers
         */
            // Keep a reference to the opened modal


        var activeModal = null;
        /**
         * Generates an associative array of modals and it's
         * respective triggers
         * @param  {array} triggers     An array of all triggers
         * @param  {string} triggerAttr The data-attribute which triggers the module
         * @return {array}
         */

        var generateTriggerMap = function generateTriggerMap(triggers, triggerAttr) {
            var triggerMap = [];
            triggers.forEach(function (trigger) {
                var targetModal = trigger.attributes[triggerAttr].value;
                if (triggerMap[targetModal] === undefined) triggerMap[targetModal] = [];
                triggerMap[targetModal].push(trigger);
            });
            return triggerMap;
        };
        /**
         * Validates whether a modal of the given id exists
         * in the DOM
         * @param  {number} id  The id of the modal
         * @return {boolean}
         */


        var validateModalPresence = function validateModalPresence(id) {
            if (!document.getElementById(id)) {
                console.warn("MicroModal: \u2757Seems like you have missed %c'".concat(id, "'"), 'background-color: #f8f9fa;color: #50596c;font-weight: bold;', 'ID somewhere in your code. Refer example below to resolve it.');
                console.warn("%cExample:", 'background-color: #f8f9fa;color: #50596c;font-weight: bold;', "<div class=\"modal\" id=\"".concat(id, "\"></div>"));
                return false;
            }
        };
        /**
         * Validates if there are modal triggers present
         * in the DOM
         * @param  {array} triggers An array of data-triggers
         * @return {boolean}
         */


        var validateTriggerPresence = function validateTriggerPresence(triggers) {
            if (triggers.length <= 0) {
                console.warn("MicroModal: \u2757Please specify at least one %c'micromodal-trigger'", 'background-color: #f8f9fa;color: #50596c;font-weight: bold;', 'data attribute.');
                console.warn("%cExample:", 'background-color: #f8f9fa;color: #50596c;font-weight: bold;', "<a href=\"#\" data-micromodal-trigger=\"my-modal\"></a>");
                return false;
            }
        };
        /**
         * Checks if triggers and their corresponding modals
         * are present in the DOM
         * @param  {array} triggers   Array of DOM nodes which have data-triggers
         * @param  {array} triggerMap Associative array of modals and their triggers
         * @return {boolean}
         */


        var validateArgs = function validateArgs(triggers, triggerMap) {
            validateTriggerPresence(triggers);
            if (!triggerMap) return true;

            for (var id in triggerMap) {
                validateModalPresence(id);
            }

            return true;
        };
        /**
         * Binds click handlers to all modal triggers
         * @param  {object} config [description]
         * @return void
         */


        var init = function init(config) {
            // Create an config object with default openTrigger
            var options = Object.assign({}, {
                openTrigger: 'data-micromodal-trigger'
            }, config); // Collects all the nodes with the trigger

            var triggers = _toConsumableArray(document.querySelectorAll("[".concat(options.openTrigger, "]"))); // Makes a mappings of modals with their trigger nodes


            var triggerMap = generateTriggerMap(triggers, options.openTrigger); // Checks if modals and triggers exist in dom

            if (options.debugMode === true && validateArgs(triggers, triggerMap) === false) return; // For every target modal creates a new instance

            for (var key in triggerMap) {
                var value = triggerMap[key];
                options.targetModal = key;
                options.triggers = _toConsumableArray(value);
                activeModal = new Modal(options); // eslint-disable-line no-new
            }
        };
        /**
         * Shows a particular modal
         * @param  {string} targetModal [The id of the modal to display]
         * @param  {object} config [The configuration object to pass]
         * @return {void}
         */


        var show = function show(targetModal, config) {
            var options = config || {};
            options.targetModal = targetModal; // Checks if modals and triggers exist in dom

            if (options.debugMode === true && validateModalPresence(targetModal) === false) return; // clear events in case previous modal wasn't close

            if (activeModal) activeModal.removeEventListeners(); // stores reference to active modal

            activeModal = new Modal(options); // eslint-disable-line no-new

            activeModal.showModal();
        };
        /**
         * Closes the active modal
         * @param  {string} targetModal [The id of the modal to close]
         * @return {void}
         */


        var close = function close(targetModal) {
            targetModal ? activeModal.closeModalById(targetModal) : activeModal.closeModal();
        };

        return {
            init: init,
            show: show,
            close: close
        };
    }();
    window.MicroModal = MicroModal;

    class ModalManager {
        constructor() {
            this.state = State.getInstance();
            this.modalReference = this.state.getElementFromReference(Events$1.modalContainer);
            this.currentModal = null;
            MicroModal.init({});
        }

        static getInstance() {
            if (!ModalManager.instance) {
                ModalManager.instance = new ModalManager();
            }

            return ModalManager.instance;
        }

        open(modal, parameters= {}) {
            if (!(modal instanceof Modal)) {
                return;
            }

            this.currentModal = modal;
            this.setBody(modal, parameters);

            MicroModal.show('payment-modal', modal.getConfig());

            return modal;
        }

        close(modal) {
            this.currentModal = null;
            MicroModal.close('payment-modal');
            this.modalReference.innerHTML = '';

            return modal;
        }

        setBody(modal, parameters = {})
        {
            if (!(modal instanceof Modal)) {
                return;
            }

            // Clean up current modal body
            while (this.modalReference.firstChild) {
                this.modalReference.removeChild(this.modalReference.firstChild);
            }

            let body = modal.render(parameters);
            let event = new GenericEvent(body, {
                'modal': modal,
                'parameters': parameters
            });

            EventDispatcher.getInstance().dispatch(
                event,
                Events.onBeforeDisplayModalEvent
            );

            // If the propagation is stopped at this stage; we assume the end-user has rendered the modal themselves.
            if (!event.isPropagationStopped()) {
                // Parse the given html
                let parser = new DOMParser();
                let element = parser.parseFromString(event.getSubject(), 'text/html');

                // Add new child to body
                this.modalReference.appendChild(element.body.firstChild);
            }
        }
    }

    class ModalListener extends EventListener {
        /**
         * When opening a modal.
         *
         * @param event
         */
        static onModalOpen(event)
        {
            ModalManager.getInstance().open(event.subject, event.parameters);
        }

        /**
         * When closing a modal.
         *
         * @param event
         */
        static onModalClose(event)
        {
            // The modal was closed manually by the user, other events might want to act upon this.
            if (event.hasParameter('manual')) {
                return;
            }

            ModalManager.getInstance().close(event.subject, event.parameters);
        }
    }

    class ModalOpenEvent extends GenericEvent {}

    var commonjsGlobal = typeof globalThis !== 'undefined' ? globalThis : typeof window !== 'undefined' ? window : typeof global !== 'undefined' ? global : typeof self !== 'undefined' ? self : {};

    function unwrapExports (x) {
        return x && x.__esModule && Object.prototype.hasOwnProperty.call(x, 'default') ? x['default'] : x;
    }

    function createCommonjsModule(fn, module) {
        return module = { exports: {} }, fn(module, module.exports), module.exports;
    }

    var jsencrypt = createCommonjsModule(function (module, exports) {
        (function (global, factory) {
            factory(exports) ;
        }(commonjsGlobal, (function (exports) {
            var BI_RM = "0123456789abcdefghijklmnopqrstuvwxyz";
            function int2char(n) {
                return BI_RM.charAt(n);
            }
            //#region BIT_OPERATIONS
            // (public) this & a
            function op_and(x, y) {
                return x & y;
            }
            // (public) this | a
            function op_or(x, y) {
                return x | y;
            }
            // (public) this ^ a
            function op_xor(x, y) {
                return x ^ y;
            }
            // (public) this & ~a
            function op_andnot(x, y) {
                return x & ~y;
            }
            // return index of lowest 1-bit in x, x < 2^31
            function lbit(x) {
                if (x == 0) {
                    return -1;
                }
                var r = 0;
                if ((x & 0xffff) == 0) {
                    x >>= 16;
                    r += 16;
                }
                if ((x & 0xff) == 0) {
                    x >>= 8;
                    r += 8;
                }
                if ((x & 0xf) == 0) {
                    x >>= 4;
                    r += 4;
                }
                if ((x & 3) == 0) {
                    x >>= 2;
                    r += 2;
                }
                if ((x & 1) == 0) {
                    ++r;
                }
                return r;
            }
            // return number of 1 bits in x
            function cbit(x) {
                var r = 0;
                while (x != 0) {
                    x &= x - 1;
                    ++r;
                }
                return r;
            }
            //#endregion BIT_OPERATIONS

            var b64map = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
            var b64pad = "=";
            function hex2b64(h) {
                var i;
                var c;
                var ret = "";
                for (i = 0; i + 3 <= h.length; i += 3) {
                    c = parseInt(h.substring(i, i + 3), 16);
                    ret += b64map.charAt(c >> 6) + b64map.charAt(c & 63);
                }
                if (i + 1 == h.length) {
                    c = parseInt(h.substring(i, i + 1), 16);
                    ret += b64map.charAt(c << 2);
                }
                else if (i + 2 == h.length) {
                    c = parseInt(h.substring(i, i + 2), 16);
                    ret += b64map.charAt(c >> 2) + b64map.charAt((c & 3) << 4);
                }
                while ((ret.length & 3) > 0) {
                    ret += b64pad;
                }
                return ret;
            }
            // convert a base64 string to hex
            function b64tohex(s) {
                var ret = "";
                var i;
                var k = 0; // b64 state, 0-3
                var slop = 0;
                for (i = 0; i < s.length; ++i) {
                    if (s.charAt(i) == b64pad) {
                        break;
                    }
                    var v = b64map.indexOf(s.charAt(i));
                    if (v < 0) {
                        continue;
                    }
                    if (k == 0) {
                        ret += int2char(v >> 2);
                        slop = v & 3;
                        k = 1;
                    }
                    else if (k == 1) {
                        ret += int2char((slop << 2) | (v >> 4));
                        slop = v & 0xf;
                        k = 2;
                    }
                    else if (k == 2) {
                        ret += int2char(slop);
                        ret += int2char(v >> 2);
                        slop = v & 3;
                        k = 3;
                    }
                    else {
                        ret += int2char((slop << 2) | (v >> 4));
                        ret += int2char(v & 0xf);
                        k = 0;
                    }
                }
                if (k == 1) {
                    ret += int2char(slop << 2);
                }
                return ret;
            }

            /*! *****************************************************************************
    Copyright (c) Microsoft Corporation. All rights reserved.
    Licensed under the Apache License, Version 2.0 (the "License"); you may not use
    this file except in compliance with the License. You may obtain a copy of the
    License at http://www.apache.org/licenses/LICENSE-2.0

    THIS CODE IS PROVIDED ON AN *AS IS* BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
    KIND, EITHER EXPRESS OR IMPLIED, INCLUDING WITHOUT LIMITATION ANY IMPLIED
    WARRANTIES OR CONDITIONS OF TITLE, FITNESS FOR A PARTICULAR PURPOSE,
    MERCHANTABLITY OR NON-INFRINGEMENT.

    See the Apache Version 2.0 License for specific language governing permissions
    and limitations under the License.
    ***************************************************************************** */
            /* global Reflect, Promise */

            var extendStatics = function(d, b) {
                extendStatics = Object.setPrototypeOf ||
                    ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
                    function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
                return extendStatics(d, b);
            };

            function __extends(d, b) {
                extendStatics(d, b);
                function __() { this.constructor = d; }
                d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
            }

            // Hex JavaScript decoder
            // Copyright (c) 2008-2013 Lapo Luchini <lapo@lapo.it>
            // Permission to use, copy, modify, and/or distribute this software for any
            // purpose with or without fee is hereby granted, provided that the above
            // copyright notice and this permission notice appear in all copies.
            //
            // THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
            // WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
            // MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
            // ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
            // WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
            // ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
            // OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
            /*jshint browser: true, strict: true, immed: true, latedef: true, undef: true, regexdash: false */
            var decoder;
            var Hex = {
                decode: function (a) {
                    var i;
                    if (decoder === undefined) {
                        var hex = "0123456789ABCDEF";
                        var ignore = " \f\n\r\t\u00A0\u2028\u2029";
                        decoder = {};
                        for (i = 0; i < 16; ++i) {
                            decoder[hex.charAt(i)] = i;
                        }
                        hex = hex.toLowerCase();
                        for (i = 10; i < 16; ++i) {
                            decoder[hex.charAt(i)] = i;
                        }
                        for (i = 0; i < ignore.length; ++i) {
                            decoder[ignore.charAt(i)] = -1;
                        }
                    }
                    var out = [];
                    var bits = 0;
                    var char_count = 0;
                    for (i = 0; i < a.length; ++i) {
                        var c = a.charAt(i);
                        if (c == "=") {
                            break;
                        }
                        c = decoder[c];
                        if (c == -1) {
                            continue;
                        }
                        if (c === undefined) {
                            throw new Error("Illegal character at offset " + i);
                        }
                        bits |= c;
                        if (++char_count >= 2) {
                            out[out.length] = bits;
                            bits = 0;
                            char_count = 0;
                        }
                        else {
                            bits <<= 4;
                        }
                    }
                    if (char_count) {
                        throw new Error("Hex encoding incomplete: 4 bits missing");
                    }
                    return out;
                }
            };

            // Base64 JavaScript decoder
            // Copyright (c) 2008-2013 Lapo Luchini <lapo@lapo.it>
            // Permission to use, copy, modify, and/or distribute this software for any
            // purpose with or without fee is hereby granted, provided that the above
            // copyright notice and this permission notice appear in all copies.
            //
            // THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
            // WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
            // MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
            // ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
            // WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
            // ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
            // OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
            /*jshint browser: true, strict: true, immed: true, latedef: true, undef: true, regexdash: false */
            var decoder$1;
            var Base64 = {
                decode: function (a) {
                    var i;
                    if (decoder$1 === undefined) {
                        var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
                        var ignore = "= \f\n\r\t\u00A0\u2028\u2029";
                        decoder$1 = Object.create(null);
                        for (i = 0; i < 64; ++i) {
                            decoder$1[b64.charAt(i)] = i;
                        }
                        for (i = 0; i < ignore.length; ++i) {
                            decoder$1[ignore.charAt(i)] = -1;
                        }
                    }
                    var out = [];
                    var bits = 0;
                    var char_count = 0;
                    for (i = 0; i < a.length; ++i) {
                        var c = a.charAt(i);
                        if (c == "=") {
                            break;
                        }
                        c = decoder$1[c];
                        if (c == -1) {
                            continue;
                        }
                        if (c === undefined) {
                            throw new Error("Illegal character at offset " + i);
                        }
                        bits |= c;
                        if (++char_count >= 4) {
                            out[out.length] = (bits >> 16);
                            out[out.length] = (bits >> 8) & 0xFF;
                            out[out.length] = bits & 0xFF;
                            bits = 0;
                            char_count = 0;
                        }
                        else {
                            bits <<= 6;
                        }
                    }
                    switch (char_count) {
                        case 1:
                            throw new Error("Base64 encoding incomplete: at least 2 bits missing");
                        case 2:
                            out[out.length] = (bits >> 10);
                            break;
                        case 3:
                            out[out.length] = (bits >> 16);
                            out[out.length] = (bits >> 8) & 0xFF;
                            break;
                    }
                    return out;
                },
                re: /-----BEGIN [^-]+-----([A-Za-z0-9+\/=\s]+)-----END [^-]+-----|begin-base64[^\n]+\n([A-Za-z0-9+\/=\s]+)====/,
                unarmor: function (a) {
                    var m = Base64.re.exec(a);
                    if (m) {
                        if (m[1]) {
                            a = m[1];
                        }
                        else if (m[2]) {
                            a = m[2];
                        }
                        else {
                            throw new Error("RegExp out of sync");
                        }
                    }
                    return Base64.decode(a);
                }
            };

            // Big integer base-10 printing library
            // Copyright (c) 2014 Lapo Luchini <lapo@lapo.it>
            // Permission to use, copy, modify, and/or distribute this software for any
            // purpose with or without fee is hereby granted, provided that the above
            // copyright notice and this permission notice appear in all copies.
            //
            // THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
            // WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
            // MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
            // ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
            // WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
            // ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
            // OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
            /*jshint browser: true, strict: true, immed: true, latedef: true, undef: true, regexdash: false */
            var max = 10000000000000; // biggest integer that can still fit 2^53 when multiplied by 256
            var Int10 = /** @class */ (function () {
                function Int10(value) {
                    this.buf = [+value || 0];
                }
                Int10.prototype.mulAdd = function (m, c) {
                    // assert(m <= 256)
                    var b = this.buf;
                    var l = b.length;
                    var i;
                    var t;
                    for (i = 0; i < l; ++i) {
                        t = b[i] * m + c;
                        if (t < max) {
                            c = 0;
                        }
                        else {
                            c = 0 | (t / max);
                            t -= c * max;
                        }
                        b[i] = t;
                    }
                    if (c > 0) {
                        b[i] = c;
                    }
                };
                Int10.prototype.sub = function (c) {
                    // assert(m <= 256)
                    var b = this.buf;
                    var l = b.length;
                    var i;
                    var t;
                    for (i = 0; i < l; ++i) {
                        t = b[i] - c;
                        if (t < 0) {
                            t += max;
                            c = 1;
                        }
                        else {
                            c = 0;
                        }
                        b[i] = t;
                    }
                    while (b[b.length - 1] === 0) {
                        b.pop();
                    }
                };
                Int10.prototype.toString = function (base) {
                    if ((base || 10) != 10) {
                        throw new Error("only base 10 is supported");
                    }
                    var b = this.buf;
                    var s = b[b.length - 1].toString();
                    for (var i = b.length - 2; i >= 0; --i) {
                        s += (max + b[i]).toString().substring(1);
                    }
                    return s;
                };
                Int10.prototype.valueOf = function () {
                    var b = this.buf;
                    var v = 0;
                    for (var i = b.length - 1; i >= 0; --i) {
                        v = v * max + b[i];
                    }
                    return v;
                };
                Int10.prototype.simplify = function () {
                    var b = this.buf;
                    return (b.length == 1) ? b[0] : this;
                };
                return Int10;
            }());

            // ASN.1 JavaScript decoder
            var ellipsis = "\u2026";
            var reTimeS = /^(\d\d)(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])([01]\d|2[0-3])(?:([0-5]\d)(?:([0-5]\d)(?:[.,](\d{1,3}))?)?)?(Z|[-+](?:[0]\d|1[0-2])([0-5]\d)?)?$/;
            var reTimeL = /^(\d\d\d\d)(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])([01]\d|2[0-3])(?:([0-5]\d)(?:([0-5]\d)(?:[.,](\d{1,3}))?)?)?(Z|[-+](?:[0]\d|1[0-2])([0-5]\d)?)?$/;
            function stringCut(str, len) {
                if (str.length > len) {
                    str = str.substring(0, len) + ellipsis;
                }
                return str;
            }
            var Stream = /** @class */ (function () {
                function Stream(enc, pos) {
                    this.hexDigits = "0123456789ABCDEF";
                    if (enc instanceof Stream) {
                        this.enc = enc.enc;
                        this.pos = enc.pos;
                    }
                    else {
                        // enc should be an array or a binary string
                        this.enc = enc;
                        this.pos = pos;
                    }
                }
                Stream.prototype.get = function (pos) {
                    if (pos === undefined) {
                        pos = this.pos++;
                    }
                    if (pos >= this.enc.length) {
                        throw new Error("Requesting byte offset " + pos + " on a stream of length " + this.enc.length);
                    }
                    return ("string" === typeof this.enc) ? this.enc.charCodeAt(pos) : this.enc[pos];
                };
                Stream.prototype.hexByte = function (b) {
                    return this.hexDigits.charAt((b >> 4) & 0xF) + this.hexDigits.charAt(b & 0xF);
                };
                Stream.prototype.hexDump = function (start, end, raw) {
                    var s = "";
                    for (var i = start; i < end; ++i) {
                        s += this.hexByte(this.get(i));
                        if (raw !== true) {
                            switch (i & 0xF) {
                                case 0x7:
                                    s += "  ";
                                    break;
                                case 0xF:
                                    s += "\n";
                                    break;
                                default:
                                    s += " ";
                            }
                        }
                    }
                    return s;
                };
                Stream.prototype.isASCII = function (start, end) {
                    for (var i = start; i < end; ++i) {
                        var c = this.get(i);
                        if (c < 32 || c > 176) {
                            return false;
                        }
                    }
                    return true;
                };
                Stream.prototype.parseStringISO = function (start, end) {
                    var s = "";
                    for (var i = start; i < end; ++i) {
                        s += String.fromCharCode(this.get(i));
                    }
                    return s;
                };
                Stream.prototype.parseStringUTF = function (start, end) {
                    var s = "";
                    for (var i = start; i < end;) {
                        var c = this.get(i++);
                        if (c < 128) {
                            s += String.fromCharCode(c);
                        }
                        else if ((c > 191) && (c < 224)) {
                            s += String.fromCharCode(((c & 0x1F) << 6) | (this.get(i++) & 0x3F));
                        }
                        else {
                            s += String.fromCharCode(((c & 0x0F) << 12) | ((this.get(i++) & 0x3F) << 6) | (this.get(i++) & 0x3F));
                        }
                    }
                    return s;
                };
                Stream.prototype.parseStringBMP = function (start, end) {
                    var str = "";
                    var hi;
                    var lo;
                    for (var i = start; i < end;) {
                        hi = this.get(i++);
                        lo = this.get(i++);
                        str += String.fromCharCode((hi << 8) | lo);
                    }
                    return str;
                };
                Stream.prototype.parseTime = function (start, end, shortYear) {
                    var s = this.parseStringISO(start, end);
                    var m = (shortYear ? reTimeS : reTimeL).exec(s);
                    if (!m) {
                        return "Unrecognized time: " + s;
                    }
                    if (shortYear) {
                        // to avoid querying the timer, use the fixed range [1970, 2069]
                        // it will conform with ITU X.400 [-10, +40] sliding window until 2030
                        m[1] = +m[1];
                        m[1] += (+m[1] < 70) ? 2000 : 1900;
                    }
                    s = m[1] + "-" + m[2] + "-" + m[3] + " " + m[4];
                    if (m[5]) {
                        s += ":" + m[5];
                        if (m[6]) {
                            s += ":" + m[6];
                            if (m[7]) {
                                s += "." + m[7];
                            }
                        }
                    }
                    if (m[8]) {
                        s += " UTC";
                        if (m[8] != "Z") {
                            s += m[8];
                            if (m[9]) {
                                s += ":" + m[9];
                            }
                        }
                    }
                    return s;
                };
                Stream.prototype.parseInteger = function (start, end) {
                    var v = this.get(start);
                    var neg = (v > 127);
                    var pad = neg ? 255 : 0;
                    var len;
                    var s = "";
                    // skip unuseful bits (not allowed in DER)
                    while (v == pad && ++start < end) {
                        v = this.get(start);
                    }
                    len = end - start;
                    if (len === 0) {
                        return neg ? -1 : 0;
                    }
                    // show bit length of huge integers
                    if (len > 4) {
                        s = v;
                        len <<= 3;
                        while (((+s ^ pad) & 0x80) == 0) {
                            s = +s << 1;
                            --len;
                        }
                        s = "(" + len + " bit)\n";
                    }
                    // decode the integer
                    if (neg) {
                        v = v - 256;
                    }
                    var n = new Int10(v);
                    for (var i = start + 1; i < end; ++i) {
                        n.mulAdd(256, this.get(i));
                    }
                    return s + n.toString();
                };
                Stream.prototype.parseBitString = function (start, end, maxLength) {
                    var unusedBit = this.get(start);
                    var lenBit = ((end - start - 1) << 3) - unusedBit;
                    var intro = "(" + lenBit + " bit)\n";
                    var s = "";
                    for (var i = start + 1; i < end; ++i) {
                        var b = this.get(i);
                        var skip = (i == end - 1) ? unusedBit : 0;
                        for (var j = 7; j >= skip; --j) {
                            s += (b >> j) & 1 ? "1" : "0";
                        }
                        if (s.length > maxLength) {
                            return intro + stringCut(s, maxLength);
                        }
                    }
                    return intro + s;
                };
                Stream.prototype.parseOctetString = function (start, end, maxLength) {
                    if (this.isASCII(start, end)) {
                        return stringCut(this.parseStringISO(start, end), maxLength);
                    }
                    var len = end - start;
                    var s = "(" + len + " byte)\n";
                    maxLength /= 2; // we work in bytes
                    if (len > maxLength) {
                        end = start + maxLength;
                    }
                    for (var i = start; i < end; ++i) {
                        s += this.hexByte(this.get(i));
                    }
                    if (len > maxLength) {
                        s += ellipsis;
                    }
                    return s;
                };
                Stream.prototype.parseOID = function (start, end, maxLength) {
                    var s = "";
                    var n = new Int10();
                    var bits = 0;
                    for (var i = start; i < end; ++i) {
                        var v = this.get(i);
                        n.mulAdd(128, v & 0x7F);
                        bits += 7;
                        if (!(v & 0x80)) { // finished
                            if (s === "") {
                                n = n.simplify();
                                if (n instanceof Int10) {
                                    n.sub(80);
                                    s = "2." + n.toString();
                                }
                                else {
                                    var m = n < 80 ? n < 40 ? 0 : 1 : 2;
                                    s = m + "." + (n - m * 40);
                                }
                            }
                            else {
                                s += "." + n.toString();
                            }
                            if (s.length > maxLength) {
                                return stringCut(s, maxLength);
                            }
                            n = new Int10();
                            bits = 0;
                        }
                    }
                    if (bits > 0) {
                        s += ".incomplete";
                    }
                    return s;
                };
                return Stream;
            }());
            var ASN1 = /** @class */ (function () {
                function ASN1(stream, header, length, tag, sub) {
                    if (!(tag instanceof ASN1Tag)) {
                        throw new Error("Invalid tag value.");
                    }
                    this.stream = stream;
                    this.header = header;
                    this.length = length;
                    this.tag = tag;
                    this.sub = sub;
                }
                ASN1.prototype.typeName = function () {
                    switch (this.tag.tagClass) {
                        case 0: // universal
                            switch (this.tag.tagNumber) {
                                case 0x00:
                                    return "EOC";
                                case 0x01:
                                    return "BOOLEAN";
                                case 0x02:
                                    return "INTEGER";
                                case 0x03:
                                    return "BIT_STRING";
                                case 0x04:
                                    return "OCTET_STRING";
                                case 0x05:
                                    return "NULL";
                                case 0x06:
                                    return "OBJECT_IDENTIFIER";
                                case 0x07:
                                    return "ObjectDescriptor";
                                case 0x08:
                                    return "EXTERNAL";
                                case 0x09:
                                    return "REAL";
                                case 0x0A:
                                    return "ENUMERATED";
                                case 0x0B:
                                    return "EMBEDDED_PDV";
                                case 0x0C:
                                    return "UTF8String";
                                case 0x10:
                                    return "SEQUENCE";
                                case 0x11:
                                    return "SET";
                                case 0x12:
                                    return "NumericString";
                                case 0x13:
                                    return "PrintableString"; // ASCII subset
                                case 0x14:
                                    return "TeletexString"; // aka T61String
                                case 0x15:
                                    return "VideotexString";
                                case 0x16:
                                    return "IA5String"; // ASCII
                                case 0x17:
                                    return "UTCTime";
                                case 0x18:
                                    return "GeneralizedTime";
                                case 0x19:
                                    return "GraphicString";
                                case 0x1A:
                                    return "VisibleString"; // ASCII subset
                                case 0x1B:
                                    return "GeneralString";
                                case 0x1C:
                                    return "UniversalString";
                                case 0x1E:
                                    return "BMPString";
                            }
                            return "Universal_" + this.tag.tagNumber.toString();
                        case 1:
                            return "Application_" + this.tag.tagNumber.toString();
                        case 2:
                            return "[" + this.tag.tagNumber.toString() + "]"; // Context
                        case 3:
                            return "Private_" + this.tag.tagNumber.toString();
                    }
                };
                ASN1.prototype.content = function (maxLength) {
                    if (this.tag === undefined) {
                        return null;
                    }
                    if (maxLength === undefined) {
                        maxLength = Infinity;
                    }
                    var content = this.posContent();
                    var len = Math.abs(this.length);
                    if (!this.tag.isUniversal()) {
                        if (this.sub !== null) {
                            return "(" + this.sub.length + " elem)";
                        }
                        return this.stream.parseOctetString(content, content + len, maxLength);
                    }
                    switch (this.tag.tagNumber) {
                        case 0x01: // BOOLEAN
                            return (this.stream.get(content) === 0) ? "false" : "true";
                        case 0x02: // INTEGER
                            return this.stream.parseInteger(content, content + len);
                        case 0x03: // BIT_STRING
                            return this.sub ? "(" + this.sub.length + " elem)" :
                                this.stream.parseBitString(content, content + len, maxLength);
                        case 0x04: // OCTET_STRING
                            return this.sub ? "(" + this.sub.length + " elem)" :
                                this.stream.parseOctetString(content, content + len, maxLength);
                        // case 0x05: // NULL
                        case 0x06: // OBJECT_IDENTIFIER
                            return this.stream.parseOID(content, content + len, maxLength);
                        // case 0x07: // ObjectDescriptor
                        // case 0x08: // EXTERNAL
                        // case 0x09: // REAL
                        // case 0x0A: // ENUMERATED
                        // case 0x0B: // EMBEDDED_PDV
                        case 0x10: // SEQUENCE
                        case 0x11: // SET
                            if (this.sub !== null) {
                                return "(" + this.sub.length + " elem)";
                            }
                            else {
                                return "(no elem)";
                            }
                        case 0x0C: // UTF8String
                            return stringCut(this.stream.parseStringUTF(content, content + len), maxLength);
                        case 0x12: // NumericString
                        case 0x13: // PrintableString
                        case 0x14: // TeletexString
                        case 0x15: // VideotexString
                        case 0x16: // IA5String
                        // case 0x19: // GraphicString
                        case 0x1A: // VisibleString
                            // case 0x1B: // GeneralString
                            // case 0x1C: // UniversalString
                            return stringCut(this.stream.parseStringISO(content, content + len), maxLength);
                        case 0x1E: // BMPString
                            return stringCut(this.stream.parseStringBMP(content, content + len), maxLength);
                        case 0x17: // UTCTime
                        case 0x18: // GeneralizedTime
                            return this.stream.parseTime(content, content + len, (this.tag.tagNumber == 0x17));
                    }
                    return null;
                };
                ASN1.prototype.toString = function () {
                    return this.typeName() + "@" + this.stream.pos + "[header:" + this.header + ",length:" + this.length + ",sub:" + ((this.sub === null) ? "null" : this.sub.length) + "]";
                };
                ASN1.prototype.toPrettyString = function (indent) {
                    if (indent === undefined) {
                        indent = "";
                    }
                    var s = indent + this.typeName() + " @" + this.stream.pos;
                    if (this.length >= 0) {
                        s += "+";
                    }
                    s += this.length;
                    if (this.tag.tagConstructed) {
                        s += " (constructed)";
                    }
                    else if ((this.tag.isUniversal() && ((this.tag.tagNumber == 0x03) || (this.tag.tagNumber == 0x04))) && (this.sub !== null)) {
                        s += " (encapsulates)";
                    }
                    s += "\n";
                    if (this.sub !== null) {
                        indent += "  ";
                        for (var i = 0, max = this.sub.length; i < max; ++i) {
                            s += this.sub[i].toPrettyString(indent);
                        }
                    }
                    return s;
                };
                ASN1.prototype.posStart = function () {
                    return this.stream.pos;
                };
                ASN1.prototype.posContent = function () {
                    return this.stream.pos + this.header;
                };
                ASN1.prototype.posEnd = function () {
                    return this.stream.pos + this.header + Math.abs(this.length);
                };
                ASN1.prototype.toHexString = function () {
                    return this.stream.hexDump(this.posStart(), this.posEnd(), true);
                };
                ASN1.decodeLength = function (stream) {
                    var buf = stream.get();
                    var len = buf & 0x7F;
                    if (len == buf) {
                        return len;
                    }
                    // no reason to use Int10, as it would be a huge buffer anyways
                    if (len > 6) {
                        throw new Error("Length over 48 bits not supported at position " + (stream.pos - 1));
                    }
                    if (len === 0) {
                        return null;
                    } // undefined
                    buf = 0;
                    for (var i = 0; i < len; ++i) {
                        buf = (buf * 256) + stream.get();
                    }
                    return buf;
                };
                /**
                 * Retrieve the hexadecimal value (as a string) of the current ASN.1 element
                 * @returns {string}
                 * @public
                 */
                ASN1.prototype.getHexStringValue = function () {
                    var hexString = this.toHexString();
                    var offset = this.header * 2;
                    var length = this.length * 2;
                    return hexString.substr(offset, length);
                };
                ASN1.decode = function (str) {
                    var stream;
                    if (!(str instanceof Stream)) {
                        stream = new Stream(str, 0);
                    }
                    else {
                        stream = str;
                    }
                    var streamStart = new Stream(stream);
                    var tag = new ASN1Tag(stream);
                    var len = ASN1.decodeLength(stream);
                    var start = stream.pos;
                    var header = start - streamStart.pos;
                    var sub = null;
                    var getSub = function () {
                        var ret = [];
                        if (len !== null) {
                            // definite length
                            var end = start + len;
                            while (stream.pos < end) {
                                ret[ret.length] = ASN1.decode(stream);
                            }
                            if (stream.pos != end) {
                                throw new Error("Content size is not correct for container starting at offset " + start);
                            }
                        }
                        else {
                            // undefined length
                            try {
                                for (;;) {
                                    var s = ASN1.decode(stream);
                                    if (s.tag.isEOC()) {
                                        break;
                                    }
                                    ret[ret.length] = s;
                                }
                                len = start - stream.pos; // undefined lengths are represented as negative values
                            }
                            catch (e) {
                                throw new Error("Exception while decoding undefined length content: " + e);
                            }
                        }
                        return ret;
                    };
                    if (tag.tagConstructed) {
                        // must have valid content
                        sub = getSub();
                    }
                    else if (tag.isUniversal() && ((tag.tagNumber == 0x03) || (tag.tagNumber == 0x04))) {
                        // sometimes BitString and OctetString are used to encapsulate ASN.1
                        try {
                            if (tag.tagNumber == 0x03) {
                                if (stream.get() != 0) {
                                    throw new Error("BIT STRINGs with unused bits cannot encapsulate.");
                                }
                            }
                            sub = getSub();
                            for (var i = 0; i < sub.length; ++i) {
                                if (sub[i].tag.isEOC()) {
                                    throw new Error("EOC is not supposed to be actual content.");
                                }
                            }
                        }
                        catch (e) {
                            // but silently ignore when they don't
                            sub = null;
                        }
                    }
                    if (sub === null) {
                        if (len === null) {
                            throw new Error("We can't skip over an invalid tag with undefined length at offset " + start);
                        }
                        stream.pos = start + Math.abs(len);
                    }
                    return new ASN1(streamStart, header, len, tag, sub);
                };
                return ASN1;
            }());
            var ASN1Tag = /** @class */ (function () {
                function ASN1Tag(stream) {
                    var buf = stream.get();
                    this.tagClass = buf >> 6;
                    this.tagConstructed = ((buf & 0x20) !== 0);
                    this.tagNumber = buf & 0x1F;
                    if (this.tagNumber == 0x1F) { // long tag
                        var n = new Int10();
                        do {
                            buf = stream.get();
                            n.mulAdd(128, buf & 0x7F);
                        } while (buf & 0x80);
                        this.tagNumber = n.simplify();
                    }
                }
                ASN1Tag.prototype.isUniversal = function () {
                    return this.tagClass === 0x00;
                };
                ASN1Tag.prototype.isEOC = function () {
                    return this.tagClass === 0x00 && this.tagNumber === 0x00;
                };
                return ASN1Tag;
            }());

            // Copyright (c) 2005  Tom Wu
            // Bits per digit
            var dbits;
            //#region
            var lowprimes = [2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41, 43, 47, 53, 59, 61, 67, 71, 73, 79, 83, 89, 97, 101, 103, 107, 109, 113, 127, 131, 137, 139, 149, 151, 157, 163, 167, 173, 179, 181, 191, 193, 197, 199, 211, 223, 227, 229, 233, 239, 241, 251, 257, 263, 269, 271, 277, 281, 283, 293, 307, 311, 313, 317, 331, 337, 347, 349, 353, 359, 367, 373, 379, 383, 389, 397, 401, 409, 419, 421, 431, 433, 439, 443, 449, 457, 461, 463, 467, 479, 487, 491, 499, 503, 509, 521, 523, 541, 547, 557, 563, 569, 571, 577, 587, 593, 599, 601, 607, 613, 617, 619, 631, 641, 643, 647, 653, 659, 661, 673, 677, 683, 691, 701, 709, 719, 727, 733, 739, 743, 751, 757, 761, 769, 773, 787, 797, 809, 811, 821, 823, 827, 829, 839, 853, 857, 859, 863, 877, 881, 883, 887, 907, 911, 919, 929, 937, 941, 947, 953, 967, 971, 977, 983, 991, 997];
            var lplim = (1 << 26) / lowprimes[lowprimes.length - 1];
            //#endregion
            // (public) Constructor
            var BigInteger = /** @class */ (function () {
                function BigInteger(a, b, c) {
                    if (a != null) {
                        if ("number" == typeof a) {
                            this.fromNumber(a, b, c);
                        }
                        else if (b == null && "string" != typeof a) {
                            this.fromString(a, 256);
                        }
                        else {
                            this.fromString(a, b);
                        }
                    }
                }
                //#region PUBLIC
                // BigInteger.prototype.toString = bnToString;
                // (public) return string representation in given radix
                BigInteger.prototype.toString = function (b) {
                    if (this.s < 0) {
                        return "-" + this.negate().toString(b);
                    }
                    var k;
                    if (b == 16) {
                        k = 4;
                    }
                    else if (b == 8) {
                        k = 3;
                    }
                    else if (b == 2) {
                        k = 1;
                    }
                    else if (b == 32) {
                        k = 5;
                    }
                    else if (b == 4) {
                        k = 2;
                    }
                    else {
                        return this.toRadix(b);
                    }
                    var km = (1 << k) - 1;
                    var d;
                    var m = false;
                    var r = "";
                    var i = this.t;
                    var p = this.DB - (i * this.DB) % k;
                    if (i-- > 0) {
                        if (p < this.DB && (d = this[i] >> p) > 0) {
                            m = true;
                            r = int2char(d);
                        }
                        while (i >= 0) {
                            if (p < k) {
                                d = (this[i] & ((1 << p) - 1)) << (k - p);
                                d |= this[--i] >> (p += this.DB - k);
                            }
                            else {
                                d = (this[i] >> (p -= k)) & km;
                                if (p <= 0) {
                                    p += this.DB;
                                    --i;
                                }
                            }
                            if (d > 0) {
                                m = true;
                            }
                            if (m) {
                                r += int2char(d);
                            }
                        }
                    }
                    return m ? r : "0";
                };
                // BigInteger.prototype.negate = bnNegate;
                // (public) -this
                BigInteger.prototype.negate = function () {
                    var r = nbi();
                    BigInteger.ZERO.subTo(this, r);
                    return r;
                };
                // BigInteger.prototype.abs = bnAbs;
                // (public) |this|
                BigInteger.prototype.abs = function () {
                    return (this.s < 0) ? this.negate() : this;
                };
                // BigInteger.prototype.compareTo = bnCompareTo;
                // (public) return + if this > a, - if this < a, 0 if equal
                BigInteger.prototype.compareTo = function (a) {
                    var r = this.s - a.s;
                    if (r != 0) {
                        return r;
                    }
                    var i = this.t;
                    r = i - a.t;
                    if (r != 0) {
                        return (this.s < 0) ? -r : r;
                    }
                    while (--i >= 0) {
                        if ((r = this[i] - a[i]) != 0) {
                            return r;
                        }
                    }
                    return 0;
                };
                // BigInteger.prototype.bitLength = bnBitLength;
                // (public) return the number of bits in "this"
                BigInteger.prototype.bitLength = function () {
                    if (this.t <= 0) {
                        return 0;
                    }
                    return this.DB * (this.t - 1) + nbits(this[this.t - 1] ^ (this.s & this.DM));
                };
                // BigInteger.prototype.mod = bnMod;
                // (public) this mod a
                BigInteger.prototype.mod = function (a) {
                    var r = nbi();
                    this.abs().divRemTo(a, null, r);
                    if (this.s < 0 && r.compareTo(BigInteger.ZERO) > 0) {
                        a.subTo(r, r);
                    }
                    return r;
                };
                // BigInteger.prototype.modPowInt = bnModPowInt;
                // (public) this^e % m, 0 <= e < 2^32
                BigInteger.prototype.modPowInt = function (e, m) {
                    var z;
                    if (e < 256 || m.isEven()) {
                        z = new Classic(m);
                    }
                    else {
                        z = new Montgomery(m);
                    }
                    return this.exp(e, z);
                };
                // BigInteger.prototype.clone = bnClone;
                // (public)
                BigInteger.prototype.clone = function () {
                    var r = nbi();
                    this.copyTo(r);
                    return r;
                };
                // BigInteger.prototype.intValue = bnIntValue;
                // (public) return value as integer
                BigInteger.prototype.intValue = function () {
                    if (this.s < 0) {
                        if (this.t == 1) {
                            return this[0] - this.DV;
                        }
                        else if (this.t == 0) {
                            return -1;
                        }
                    }
                    else if (this.t == 1) {
                        return this[0];
                    }
                    else if (this.t == 0) {
                        return 0;
                    }
                    // assumes 16 < DB < 32
                    return ((this[1] & ((1 << (32 - this.DB)) - 1)) << this.DB) | this[0];
                };
                // BigInteger.prototype.byteValue = bnByteValue;
                // (public) return value as byte
                BigInteger.prototype.byteValue = function () {
                    return (this.t == 0) ? this.s : (this[0] << 24) >> 24;
                };
                // BigInteger.prototype.shortValue = bnShortValue;
                // (public) return value as short (assumes DB>=16)
                BigInteger.prototype.shortValue = function () {
                    return (this.t == 0) ? this.s : (this[0] << 16) >> 16;
                };
                // BigInteger.prototype.signum = bnSigNum;
                // (public) 0 if this == 0, 1 if this > 0
                BigInteger.prototype.signum = function () {
                    if (this.s < 0) {
                        return -1;
                    }
                    else if (this.t <= 0 || (this.t == 1 && this[0] <= 0)) {
                        return 0;
                    }
                    else {
                        return 1;
                    }
                };
                // BigInteger.prototype.toByteArray = bnToByteArray;
                // (public) convert to bigendian byte array
                BigInteger.prototype.toByteArray = function () {
                    var i = this.t;
                    var r = [];
                    r[0] = this.s;
                    var p = this.DB - (i * this.DB) % 8;
                    var d;
                    var k = 0;
                    if (i-- > 0) {
                        if (p < this.DB && (d = this[i] >> p) != (this.s & this.DM) >> p) {
                            r[k++] = d | (this.s << (this.DB - p));
                        }
                        while (i >= 0) {
                            if (p < 8) {
                                d = (this[i] & ((1 << p) - 1)) << (8 - p);
                                d |= this[--i] >> (p += this.DB - 8);
                            }
                            else {
                                d = (this[i] >> (p -= 8)) & 0xff;
                                if (p <= 0) {
                                    p += this.DB;
                                    --i;
                                }
                            }
                            if ((d & 0x80) != 0) {
                                d |= -256;
                            }
                            if (k == 0 && (this.s & 0x80) != (d & 0x80)) {
                                ++k;
                            }
                            if (k > 0 || d != this.s) {
                                r[k++] = d;
                            }
                        }
                    }
                    return r;
                };
                // BigInteger.prototype.equals = bnEquals;
                BigInteger.prototype.equals = function (a) {
                    return (this.compareTo(a) == 0);
                };
                // BigInteger.prototype.min = bnMin;
                BigInteger.prototype.min = function (a) {
                    return (this.compareTo(a) < 0) ? this : a;
                };
                // BigInteger.prototype.max = bnMax;
                BigInteger.prototype.max = function (a) {
                    return (this.compareTo(a) > 0) ? this : a;
                };
                // BigInteger.prototype.and = bnAnd;
                BigInteger.prototype.and = function (a) {
                    var r = nbi();
                    this.bitwiseTo(a, op_and, r);
                    return r;
                };
                // BigInteger.prototype.or = bnOr;
                BigInteger.prototype.or = function (a) {
                    var r = nbi();
                    this.bitwiseTo(a, op_or, r);
                    return r;
                };
                // BigInteger.prototype.xor = bnXor;
                BigInteger.prototype.xor = function (a) {
                    var r = nbi();
                    this.bitwiseTo(a, op_xor, r);
                    return r;
                };
                // BigInteger.prototype.andNot = bnAndNot;
                BigInteger.prototype.andNot = function (a) {
                    var r = nbi();
                    this.bitwiseTo(a, op_andnot, r);
                    return r;
                };
                // BigInteger.prototype.not = bnNot;
                // (public) ~this
                BigInteger.prototype.not = function () {
                    var r = nbi();
                    for (var i = 0; i < this.t; ++i) {
                        r[i] = this.DM & ~this[i];
                    }
                    r.t = this.t;
                    r.s = ~this.s;
                    return r;
                };
                // BigInteger.prototype.shiftLeft = bnShiftLeft;
                // (public) this << n
                BigInteger.prototype.shiftLeft = function (n) {
                    var r = nbi();
                    if (n < 0) {
                        this.rShiftTo(-n, r);
                    }
                    else {
                        this.lShiftTo(n, r);
                    }
                    return r;
                };
                // BigInteger.prototype.shiftRight = bnShiftRight;
                // (public) this >> n
                BigInteger.prototype.shiftRight = function (n) {
                    var r = nbi();
                    if (n < 0) {
                        this.lShiftTo(-n, r);
                    }
                    else {
                        this.rShiftTo(n, r);
                    }
                    return r;
                };
                // BigInteger.prototype.getLowestSetBit = bnGetLowestSetBit;
                // (public) returns index of lowest 1-bit (or -1 if none)
                BigInteger.prototype.getLowestSetBit = function () {
                    for (var i = 0; i < this.t; ++i) {
                        if (this[i] != 0) {
                            return i * this.DB + lbit(this[i]);
                        }
                    }
                    if (this.s < 0) {
                        return this.t * this.DB;
                    }
                    return -1;
                };
                // BigInteger.prototype.bitCount = bnBitCount;
                // (public) return number of set bits
                BigInteger.prototype.bitCount = function () {
                    var r = 0;
                    var x = this.s & this.DM;
                    for (var i = 0; i < this.t; ++i) {
                        r += cbit(this[i] ^ x);
                    }
                    return r;
                };
                // BigInteger.prototype.testBit = bnTestBit;
                // (public) true iff nth bit is set
                BigInteger.prototype.testBit = function (n) {
                    var j = Math.floor(n / this.DB);
                    if (j >= this.t) {
                        return (this.s != 0);
                    }
                    return ((this[j] & (1 << (n % this.DB))) != 0);
                };
                // BigInteger.prototype.setBit = bnSetBit;
                // (public) this | (1<<n)
                BigInteger.prototype.setBit = function (n) {
                    return this.changeBit(n, op_or);
                };
                // BigInteger.prototype.clearBit = bnClearBit;
                // (public) this & ~(1<<n)
                BigInteger.prototype.clearBit = function (n) {
                    return this.changeBit(n, op_andnot);
                };
                // BigInteger.prototype.flipBit = bnFlipBit;
                // (public) this ^ (1<<n)
                BigInteger.prototype.flipBit = function (n) {
                    return this.changeBit(n, op_xor);
                };
                // BigInteger.prototype.add = bnAdd;
                // (public) this + a
                BigInteger.prototype.add = function (a) {
                    var r = nbi();
                    this.addTo(a, r);
                    return r;
                };
                // BigInteger.prototype.subtract = bnSubtract;
                // (public) this - a
                BigInteger.prototype.subtract = function (a) {
                    var r = nbi();
                    this.subTo(a, r);
                    return r;
                };
                // BigInteger.prototype.multiply = bnMultiply;
                // (public) this * a
                BigInteger.prototype.multiply = function (a) {
                    var r = nbi();
                    this.multiplyTo(a, r);
                    return r;
                };
                // BigInteger.prototype.divide = bnDivide;
                // (public) this / a
                BigInteger.prototype.divide = function (a) {
                    var r = nbi();
                    this.divRemTo(a, r, null);
                    return r;
                };
                // BigInteger.prototype.remainder = bnRemainder;
                // (public) this % a
                BigInteger.prototype.remainder = function (a) {
                    var r = nbi();
                    this.divRemTo(a, null, r);
                    return r;
                };
                // BigInteger.prototype.divideAndRemainder = bnDivideAndRemainder;
                // (public) [this/a,this%a]
                BigInteger.prototype.divideAndRemainder = function (a) {
                    var q = nbi();
                    var r = nbi();
                    this.divRemTo(a, q, r);
                    return [q, r];
                };
                // BigInteger.prototype.modPow = bnModPow;
                // (public) this^e % m (HAC 14.85)
                BigInteger.prototype.modPow = function (e, m) {
                    var i = e.bitLength();
                    var k;
                    var r = nbv(1);
                    var z;
                    if (i <= 0) {
                        return r;
                    }
                    else if (i < 18) {
                        k = 1;
                    }
                    else if (i < 48) {
                        k = 3;
                    }
                    else if (i < 144) {
                        k = 4;
                    }
                    else if (i < 768) {
                        k = 5;
                    }
                    else {
                        k = 6;
                    }
                    if (i < 8) {
                        z = new Classic(m);
                    }
                    else if (m.isEven()) {
                        z = new Barrett(m);
                    }
                    else {
                        z = new Montgomery(m);
                    }
                    // precomputation
                    var g = [];
                    var n = 3;
                    var k1 = k - 1;
                    var km = (1 << k) - 1;
                    g[1] = z.convert(this);
                    if (k > 1) {
                        var g2 = nbi();
                        z.sqrTo(g[1], g2);
                        while (n <= km) {
                            g[n] = nbi();
                            z.mulTo(g2, g[n - 2], g[n]);
                            n += 2;
                        }
                    }
                    var j = e.t - 1;
                    var w;
                    var is1 = true;
                    var r2 = nbi();
                    var t;
                    i = nbits(e[j]) - 1;
                    while (j >= 0) {
                        if (i >= k1) {
                            w = (e[j] >> (i - k1)) & km;
                        }
                        else {
                            w = (e[j] & ((1 << (i + 1)) - 1)) << (k1 - i);
                            if (j > 0) {
                                w |= e[j - 1] >> (this.DB + i - k1);
                            }
                        }
                        n = k;
                        while ((w & 1) == 0) {
                            w >>= 1;
                            --n;
                        }
                        if ((i -= n) < 0) {
                            i += this.DB;
                            --j;
                        }
                        if (is1) { // ret == 1, don't bother squaring or multiplying it
                            g[w].copyTo(r);
                            is1 = false;
                        }
                        else {
                            while (n > 1) {
                                z.sqrTo(r, r2);
                                z.sqrTo(r2, r);
                                n -= 2;
                            }
                            if (n > 0) {
                                z.sqrTo(r, r2);
                            }
                            else {
                                t = r;
                                r = r2;
                                r2 = t;
                            }
                            z.mulTo(r2, g[w], r);
                        }
                        while (j >= 0 && (e[j] & (1 << i)) == 0) {
                            z.sqrTo(r, r2);
                            t = r;
                            r = r2;
                            r2 = t;
                            if (--i < 0) {
                                i = this.DB - 1;
                                --j;
                            }
                        }
                    }
                    return z.revert(r);
                };
                // BigInteger.prototype.modInverse = bnModInverse;
                // (public) 1/this % m (HAC 14.61)
                BigInteger.prototype.modInverse = function (m) {
                    var ac = m.isEven();
                    if ((this.isEven() && ac) || m.signum() == 0) {
                        return BigInteger.ZERO;
                    }
                    var u = m.clone();
                    var v = this.clone();
                    var a = nbv(1);
                    var b = nbv(0);
                    var c = nbv(0);
                    var d = nbv(1);
                    while (u.signum() != 0) {
                        while (u.isEven()) {
                            u.rShiftTo(1, u);
                            if (ac) {
                                if (!a.isEven() || !b.isEven()) {
                                    a.addTo(this, a);
                                    b.subTo(m, b);
                                }
                                a.rShiftTo(1, a);
                            }
                            else if (!b.isEven()) {
                                b.subTo(m, b);
                            }
                            b.rShiftTo(1, b);
                        }
                        while (v.isEven()) {
                            v.rShiftTo(1, v);
                            if (ac) {
                                if (!c.isEven() || !d.isEven()) {
                                    c.addTo(this, c);
                                    d.subTo(m, d);
                                }
                                c.rShiftTo(1, c);
                            }
                            else if (!d.isEven()) {
                                d.subTo(m, d);
                            }
                            d.rShiftTo(1, d);
                        }
                        if (u.compareTo(v) >= 0) {
                            u.subTo(v, u);
                            if (ac) {
                                a.subTo(c, a);
                            }
                            b.subTo(d, b);
                        }
                        else {
                            v.subTo(u, v);
                            if (ac) {
                                c.subTo(a, c);
                            }
                            d.subTo(b, d);
                        }
                    }
                    if (v.compareTo(BigInteger.ONE) != 0) {
                        return BigInteger.ZERO;
                    }
                    if (d.compareTo(m) >= 0) {
                        return d.subtract(m);
                    }
                    if (d.signum() < 0) {
                        d.addTo(m, d);
                    }
                    else {
                        return d;
                    }
                    if (d.signum() < 0) {
                        return d.add(m);
                    }
                    else {
                        return d;
                    }
                };
                // BigInteger.prototype.pow = bnPow;
                // (public) this^e
                BigInteger.prototype.pow = function (e) {
                    return this.exp(e, new NullExp());
                };
                // BigInteger.prototype.gcd = bnGCD;
                // (public) gcd(this,a) (HAC 14.54)
                BigInteger.prototype.gcd = function (a) {
                    var x = (this.s < 0) ? this.negate() : this.clone();
                    var y = (a.s < 0) ? a.negate() : a.clone();
                    if (x.compareTo(y) < 0) {
                        var t = x;
                        x = y;
                        y = t;
                    }
                    var i = x.getLowestSetBit();
                    var g = y.getLowestSetBit();
                    if (g < 0) {
                        return x;
                    }
                    if (i < g) {
                        g = i;
                    }
                    if (g > 0) {
                        x.rShiftTo(g, x);
                        y.rShiftTo(g, y);
                    }
                    while (x.signum() > 0) {
                        if ((i = x.getLowestSetBit()) > 0) {
                            x.rShiftTo(i, x);
                        }
                        if ((i = y.getLowestSetBit()) > 0) {
                            y.rShiftTo(i, y);
                        }
                        if (x.compareTo(y) >= 0) {
                            x.subTo(y, x);
                            x.rShiftTo(1, x);
                        }
                        else {
                            y.subTo(x, y);
                            y.rShiftTo(1, y);
                        }
                    }
                    if (g > 0) {
                        y.lShiftTo(g, y);
                    }
                    return y;
                };
                // BigInteger.prototype.isProbablePrime = bnIsProbablePrime;
                // (public) test primality with certainty >= 1-.5^t
                BigInteger.prototype.isProbablePrime = function (t) {
                    var i;
                    var x = this.abs();
                    if (x.t == 1 && x[0] <= lowprimes[lowprimes.length - 1]) {
                        for (i = 0; i < lowprimes.length; ++i) {
                            if (x[0] == lowprimes[i]) {
                                return true;
                            }
                        }
                        return false;
                    }
                    if (x.isEven()) {
                        return false;
                    }
                    i = 1;
                    while (i < lowprimes.length) {
                        var m = lowprimes[i];
                        var j = i + 1;
                        while (j < lowprimes.length && m < lplim) {
                            m *= lowprimes[j++];
                        }
                        m = x.modInt(m);
                        while (i < j) {
                            if (m % lowprimes[i++] == 0) {
                                return false;
                            }
                        }
                    }
                    return x.millerRabin(t);
                };
                //#endregion PUBLIC
                //#region PROTECTED
                // BigInteger.prototype.copyTo = bnpCopyTo;
                // (protected) copy this to r
                BigInteger.prototype.copyTo = function (r) {
                    for (var i = this.t - 1; i >= 0; --i) {
                        r[i] = this[i];
                    }
                    r.t = this.t;
                    r.s = this.s;
                };
                // BigInteger.prototype.fromInt = bnpFromInt;
                // (protected) set from integer value x, -DV <= x < DV
                BigInteger.prototype.fromInt = function (x) {
                    this.t = 1;
                    this.s = (x < 0) ? -1 : 0;
                    if (x > 0) {
                        this[0] = x;
                    }
                    else if (x < -1) {
                        this[0] = x + this.DV;
                    }
                    else {
                        this.t = 0;
                    }
                };
                // BigInteger.prototype.fromString = bnpFromString;
                // (protected) set from string and radix
                BigInteger.prototype.fromString = function (s, b) {
                    var k;
                    if (b == 16) {
                        k = 4;
                    }
                    else if (b == 8) {
                        k = 3;
                    }
                    else if (b == 256) {
                        k = 8;
                        /* byte array */
                    }
                    else if (b == 2) {
                        k = 1;
                    }
                    else if (b == 32) {
                        k = 5;
                    }
                    else if (b == 4) {
                        k = 2;
                    }
                    else {
                        this.fromRadix(s, b);
                        return;
                    }
                    this.t = 0;
                    this.s = 0;
                    var i = s.length;
                    var mi = false;
                    var sh = 0;
                    while (--i >= 0) {
                        var x = (k == 8) ? (+s[i]) & 0xff : intAt(s, i);
                        if (x < 0) {
                            if (s.charAt(i) == "-") {
                                mi = true;
                            }
                            continue;
                        }
                        mi = false;
                        if (sh == 0) {
                            this[this.t++] = x;
                        }
                        else if (sh + k > this.DB) {
                            this[this.t - 1] |= (x & ((1 << (this.DB - sh)) - 1)) << sh;
                            this[this.t++] = (x >> (this.DB - sh));
                        }
                        else {
                            this[this.t - 1] |= x << sh;
                        }
                        sh += k;
                        if (sh >= this.DB) {
                            sh -= this.DB;
                        }
                    }
                    if (k == 8 && ((+s[0]) & 0x80) != 0) {
                        this.s = -1;
                        if (sh > 0) {
                            this[this.t - 1] |= ((1 << (this.DB - sh)) - 1) << sh;
                        }
                    }
                    this.clamp();
                    if (mi) {
                        BigInteger.ZERO.subTo(this, this);
                    }
                };
                // BigInteger.prototype.clamp = bnpClamp;
                // (protected) clamp off excess high words
                BigInteger.prototype.clamp = function () {
                    var c = this.s & this.DM;
                    while (this.t > 0 && this[this.t - 1] == c) {
                        --this.t;
                    }
                };
                // BigInteger.prototype.dlShiftTo = bnpDLShiftTo;
                // (protected) r = this << n*DB
                BigInteger.prototype.dlShiftTo = function (n, r) {
                    var i;
                    for (i = this.t - 1; i >= 0; --i) {
                        r[i + n] = this[i];
                    }
                    for (i = n - 1; i >= 0; --i) {
                        r[i] = 0;
                    }
                    r.t = this.t + n;
                    r.s = this.s;
                };
                // BigInteger.prototype.drShiftTo = bnpDRShiftTo;
                // (protected) r = this >> n*DB
                BigInteger.prototype.drShiftTo = function (n, r) {
                    for (var i = n; i < this.t; ++i) {
                        r[i - n] = this[i];
                    }
                    r.t = Math.max(this.t - n, 0);
                    r.s = this.s;
                };
                // BigInteger.prototype.lShiftTo = bnpLShiftTo;
                // (protected) r = this << n
                BigInteger.prototype.lShiftTo = function (n, r) {
                    var bs = n % this.DB;
                    var cbs = this.DB - bs;
                    var bm = (1 << cbs) - 1;
                    var ds = Math.floor(n / this.DB);
                    var c = (this.s << bs) & this.DM;
                    for (var i = this.t - 1; i >= 0; --i) {
                        r[i + ds + 1] = (this[i] >> cbs) | c;
                        c = (this[i] & bm) << bs;
                    }
                    for (var i = ds - 1; i >= 0; --i) {
                        r[i] = 0;
                    }
                    r[ds] = c;
                    r.t = this.t + ds + 1;
                    r.s = this.s;
                    r.clamp();
                };
                // BigInteger.prototype.rShiftTo = bnpRShiftTo;
                // (protected) r = this >> n
                BigInteger.prototype.rShiftTo = function (n, r) {
                    r.s = this.s;
                    var ds = Math.floor(n / this.DB);
                    if (ds >= this.t) {
                        r.t = 0;
                        return;
                    }
                    var bs = n % this.DB;
                    var cbs = this.DB - bs;
                    var bm = (1 << bs) - 1;
                    r[0] = this[ds] >> bs;
                    for (var i = ds + 1; i < this.t; ++i) {
                        r[i - ds - 1] |= (this[i] & bm) << cbs;
                        r[i - ds] = this[i] >> bs;
                    }
                    if (bs > 0) {
                        r[this.t - ds - 1] |= (this.s & bm) << cbs;
                    }
                    r.t = this.t - ds;
                    r.clamp();
                };
                // BigInteger.prototype.subTo = bnpSubTo;
                // (protected) r = this - a
                BigInteger.prototype.subTo = function (a, r) {
                    var i = 0;
                    var c = 0;
                    var m = Math.min(a.t, this.t);
                    while (i < m) {
                        c += this[i] - a[i];
                        r[i++] = c & this.DM;
                        c >>= this.DB;
                    }
                    if (a.t < this.t) {
                        c -= a.s;
                        while (i < this.t) {
                            c += this[i];
                            r[i++] = c & this.DM;
                            c >>= this.DB;
                        }
                        c += this.s;
                    }
                    else {
                        c += this.s;
                        while (i < a.t) {
                            c -= a[i];
                            r[i++] = c & this.DM;
                            c >>= this.DB;
                        }
                        c -= a.s;
                    }
                    r.s = (c < 0) ? -1 : 0;
                    if (c < -1) {
                        r[i++] = this.DV + c;
                    }
                    else if (c > 0) {
                        r[i++] = c;
                    }
                    r.t = i;
                    r.clamp();
                };
                // BigInteger.prototype.multiplyTo = bnpMultiplyTo;
                // (protected) r = this * a, r != this,a (HAC 14.12)
                // "this" should be the larger one if appropriate.
                BigInteger.prototype.multiplyTo = function (a, r) {
                    var x = this.abs();
                    var y = a.abs();
                    var i = x.t;
                    r.t = i + y.t;
                    while (--i >= 0) {
                        r[i] = 0;
                    }
                    for (i = 0; i < y.t; ++i) {
                        r[i + x.t] = x.am(0, y[i], r, i, 0, x.t);
                    }
                    r.s = 0;
                    r.clamp();
                    if (this.s != a.s) {
                        BigInteger.ZERO.subTo(r, r);
                    }
                };
                // BigInteger.prototype.squareTo = bnpSquareTo;
                // (protected) r = this^2, r != this (HAC 14.16)
                BigInteger.prototype.squareTo = function (r) {
                    var x = this.abs();
                    var i = r.t = 2 * x.t;
                    while (--i >= 0) {
                        r[i] = 0;
                    }
                    for (i = 0; i < x.t - 1; ++i) {
                        var c = x.am(i, x[i], r, 2 * i, 0, 1);
                        if ((r[i + x.t] += x.am(i + 1, 2 * x[i], r, 2 * i + 1, c, x.t - i - 1)) >= x.DV) {
                            r[i + x.t] -= x.DV;
                            r[i + x.t + 1] = 1;
                        }
                    }
                    if (r.t > 0) {
                        r[r.t - 1] += x.am(i, x[i], r, 2 * i, 0, 1);
                    }
                    r.s = 0;
                    r.clamp();
                };
                // BigInteger.prototype.divRemTo = bnpDivRemTo;
                // (protected) divide this by m, quotient and remainder to q, r (HAC 14.20)
                // r != q, this != m.  q or r may be null.
                BigInteger.prototype.divRemTo = function (m, q, r) {
                    var pm = m.abs();
                    if (pm.t <= 0) {
                        return;
                    }
                    var pt = this.abs();
                    if (pt.t < pm.t) {
                        if (q != null) {
                            q.fromInt(0);
                        }
                        if (r != null) {
                            this.copyTo(r);
                        }
                        return;
                    }
                    if (r == null) {
                        r = nbi();
                    }
                    var y = nbi();
                    var ts = this.s;
                    var ms = m.s;
                    var nsh = this.DB - nbits(pm[pm.t - 1]); // normalize modulus
                    if (nsh > 0) {
                        pm.lShiftTo(nsh, y);
                        pt.lShiftTo(nsh, r);
                    }
                    else {
                        pm.copyTo(y);
                        pt.copyTo(r);
                    }
                    var ys = y.t;
                    var y0 = y[ys - 1];
                    if (y0 == 0) {
                        return;
                    }
                    var yt = y0 * (1 << this.F1) + ((ys > 1) ? y[ys - 2] >> this.F2 : 0);
                    var d1 = this.FV / yt;
                    var d2 = (1 << this.F1) / yt;
                    var e = 1 << this.F2;
                    var i = r.t;
                    var j = i - ys;
                    var t = (q == null) ? nbi() : q;
                    y.dlShiftTo(j, t);
                    if (r.compareTo(t) >= 0) {
                        r[r.t++] = 1;
                        r.subTo(t, r);
                    }
                    BigInteger.ONE.dlShiftTo(ys, t);
                    t.subTo(y, y); // "negative" y so we can replace sub with am later
                    while (y.t < ys) {
                        y[y.t++] = 0;
                    }
                    while (--j >= 0) {
                        // Estimate quotient digit
                        var qd = (r[--i] == y0) ? this.DM : Math.floor(r[i] * d1 + (r[i - 1] + e) * d2);
                        if ((r[i] += y.am(0, qd, r, j, 0, ys)) < qd) { // Try it out
                            y.dlShiftTo(j, t);
                            r.subTo(t, r);
                            while (r[i] < --qd) {
                                r.subTo(t, r);
                            }
                        }
                    }
                    if (q != null) {
                        r.drShiftTo(ys, q);
                        if (ts != ms) {
                            BigInteger.ZERO.subTo(q, q);
                        }
                    }
                    r.t = ys;
                    r.clamp();
                    if (nsh > 0) {
                        r.rShiftTo(nsh, r);
                    } // Denormalize remainder
                    if (ts < 0) {
                        BigInteger.ZERO.subTo(r, r);
                    }
                };
                // BigInteger.prototype.invDigit = bnpInvDigit;
                // (protected) return "-1/this % 2^DB"; useful for Mont. reduction
                // justification:
                //         xy == 1 (mod m)
                //         xy =  1+km
                //   xy(2-xy) = (1+km)(1-km)
                // x[y(2-xy)] = 1-k^2m^2
                // x[y(2-xy)] == 1 (mod m^2)
                // if y is 1/x mod m, then y(2-xy) is 1/x mod m^2
                // should reduce x and y(2-xy) by m^2 at each step to keep size bounded.
                // JS multiply "overflows" differently from C/C++, so care is needed here.
                BigInteger.prototype.invDigit = function () {
                    if (this.t < 1) {
                        return 0;
                    }
                    var x = this[0];
                    if ((x & 1) == 0) {
                        return 0;
                    }
                    var y = x & 3; // y == 1/x mod 2^2
                    y = (y * (2 - (x & 0xf) * y)) & 0xf; // y == 1/x mod 2^4
                    y = (y * (2 - (x & 0xff) * y)) & 0xff; // y == 1/x mod 2^8
                    y = (y * (2 - (((x & 0xffff) * y) & 0xffff))) & 0xffff; // y == 1/x mod 2^16
                    // last step - calculate inverse mod DV directly;
                    // assumes 16 < DB <= 32 and assumes ability to handle 48-bit ints
                    y = (y * (2 - x * y % this.DV)) % this.DV; // y == 1/x mod 2^dbits
                    // we really want the negative inverse, and -DV < y < DV
                    return (y > 0) ? this.DV - y : -y;
                };
                // BigInteger.prototype.isEven = bnpIsEven;
                // (protected) true iff this is even
                BigInteger.prototype.isEven = function () {
                    return ((this.t > 0) ? (this[0] & 1) : this.s) == 0;
                };
                // BigInteger.prototype.exp = bnpExp;
                // (protected) this^e, e < 2^32, doing sqr and mul with "r" (HAC 14.79)
                BigInteger.prototype.exp = function (e, z) {
                    if (e > 0xffffffff || e < 1) {
                        return BigInteger.ONE;
                    }
                    var r = nbi();
                    var r2 = nbi();
                    var g = z.convert(this);
                    var i = nbits(e) - 1;
                    g.copyTo(r);
                    while (--i >= 0) {
                        z.sqrTo(r, r2);
                        if ((e & (1 << i)) > 0) {
                            z.mulTo(r2, g, r);
                        }
                        else {
                            var t = r;
                            r = r2;
                            r2 = t;
                        }
                    }
                    return z.revert(r);
                };
                // BigInteger.prototype.chunkSize = bnpChunkSize;
                // (protected) return x s.t. r^x < DV
                BigInteger.prototype.chunkSize = function (r) {
                    return Math.floor(Math.LN2 * this.DB / Math.log(r));
                };
                // BigInteger.prototype.toRadix = bnpToRadix;
                // (protected) convert to radix string
                BigInteger.prototype.toRadix = function (b) {
                    if (b == null) {
                        b = 10;
                    }
                    if (this.signum() == 0 || b < 2 || b > 36) {
                        return "0";
                    }
                    var cs = this.chunkSize(b);
                    var a = Math.pow(b, cs);
                    var d = nbv(a);
                    var y = nbi();
                    var z = nbi();
                    var r = "";
                    this.divRemTo(d, y, z);
                    while (y.signum() > 0) {
                        r = (a + z.intValue()).toString(b).substr(1) + r;
                        y.divRemTo(d, y, z);
                    }
                    return z.intValue().toString(b) + r;
                };
                // BigInteger.prototype.fromRadix = bnpFromRadix;
                // (protected) convert from radix string
                BigInteger.prototype.fromRadix = function (s, b) {
                    this.fromInt(0);
                    if (b == null) {
                        b = 10;
                    }
                    var cs = this.chunkSize(b);
                    var d = Math.pow(b, cs);
                    var mi = false;
                    var j = 0;
                    var w = 0;
                    for (var i = 0; i < s.length; ++i) {
                        var x = intAt(s, i);
                        if (x < 0) {
                            if (s.charAt(i) == "-" && this.signum() == 0) {
                                mi = true;
                            }
                            continue;
                        }
                        w = b * w + x;
                        if (++j >= cs) {
                            this.dMultiply(d);
                            this.dAddOffset(w, 0);
                            j = 0;
                            w = 0;
                        }
                    }
                    if (j > 0) {
                        this.dMultiply(Math.pow(b, j));
                        this.dAddOffset(w, 0);
                    }
                    if (mi) {
                        BigInteger.ZERO.subTo(this, this);
                    }
                };
                // BigInteger.prototype.fromNumber = bnpFromNumber;
                // (protected) alternate constructor
                BigInteger.prototype.fromNumber = function (a, b, c) {
                    if ("number" == typeof b) {
                        // new BigInteger(int,int,RNG)
                        if (a < 2) {
                            this.fromInt(1);
                        }
                        else {
                            this.fromNumber(a, c);
                            if (!this.testBit(a - 1)) {
                                // force MSB set
                                this.bitwiseTo(BigInteger.ONE.shiftLeft(a - 1), op_or, this);
                            }
                            if (this.isEven()) {
                                this.dAddOffset(1, 0);
                            } // force odd
                            while (!this.isProbablePrime(b)) {
                                this.dAddOffset(2, 0);
                                if (this.bitLength() > a) {
                                    this.subTo(BigInteger.ONE.shiftLeft(a - 1), this);
                                }
                            }
                        }
                    }
                    else {
                        // new BigInteger(int,RNG)
                        var x = [];
                        var t = a & 7;
                        x.length = (a >> 3) + 1;
                        b.nextBytes(x);
                        if (t > 0) {
                            x[0] &= ((1 << t) - 1);
                        }
                        else {
                            x[0] = 0;
                        }
                        this.fromString(x, 256);
                    }
                };
                // BigInteger.prototype.bitwiseTo = bnpBitwiseTo;
                // (protected) r = this op a (bitwise)
                BigInteger.prototype.bitwiseTo = function (a, op, r) {
                    var i;
                    var f;
                    var m = Math.min(a.t, this.t);
                    for (i = 0; i < m; ++i) {
                        r[i] = op(this[i], a[i]);
                    }
                    if (a.t < this.t) {
                        f = a.s & this.DM;
                        for (i = m; i < this.t; ++i) {
                            r[i] = op(this[i], f);
                        }
                        r.t = this.t;
                    }
                    else {
                        f = this.s & this.DM;
                        for (i = m; i < a.t; ++i) {
                            r[i] = op(f, a[i]);
                        }
                        r.t = a.t;
                    }
                    r.s = op(this.s, a.s);
                    r.clamp();
                };
                // BigInteger.prototype.changeBit = bnpChangeBit;
                // (protected) this op (1<<n)
                BigInteger.prototype.changeBit = function (n, op) {
                    var r = BigInteger.ONE.shiftLeft(n);
                    this.bitwiseTo(r, op, r);
                    return r;
                };
                // BigInteger.prototype.addTo = bnpAddTo;
                // (protected) r = this + a
                BigInteger.prototype.addTo = function (a, r) {
                    var i = 0;
                    var c = 0;
                    var m = Math.min(a.t, this.t);
                    while (i < m) {
                        c += this[i] + a[i];
                        r[i++] = c & this.DM;
                        c >>= this.DB;
                    }
                    if (a.t < this.t) {
                        c += a.s;
                        while (i < this.t) {
                            c += this[i];
                            r[i++] = c & this.DM;
                            c >>= this.DB;
                        }
                        c += this.s;
                    }
                    else {
                        c += this.s;
                        while (i < a.t) {
                            c += a[i];
                            r[i++] = c & this.DM;
                            c >>= this.DB;
                        }
                        c += a.s;
                    }
                    r.s = (c < 0) ? -1 : 0;
                    if (c > 0) {
                        r[i++] = c;
                    }
                    else if (c < -1) {
                        r[i++] = this.DV + c;
                    }
                    r.t = i;
                    r.clamp();
                };
                // BigInteger.prototype.dMultiply = bnpDMultiply;
                // (protected) this *= n, this >= 0, 1 < n < DV
                BigInteger.prototype.dMultiply = function (n) {
                    this[this.t] = this.am(0, n - 1, this, 0, 0, this.t);
                    ++this.t;
                    this.clamp();
                };
                // BigInteger.prototype.dAddOffset = bnpDAddOffset;
                // (protected) this += n << w words, this >= 0
                BigInteger.prototype.dAddOffset = function (n, w) {
                    if (n == 0) {
                        return;
                    }
                    while (this.t <= w) {
                        this[this.t++] = 0;
                    }
                    this[w] += n;
                    while (this[w] >= this.DV) {
                        this[w] -= this.DV;
                        if (++w >= this.t) {
                            this[this.t++] = 0;
                        }
                        ++this[w];
                    }
                };
                // BigInteger.prototype.multiplyLowerTo = bnpMultiplyLowerTo;
                // (protected) r = lower n words of "this * a", a.t <= n
                // "this" should be the larger one if appropriate.
                BigInteger.prototype.multiplyLowerTo = function (a, n, r) {
                    var i = Math.min(this.t + a.t, n);
                    r.s = 0; // assumes a,this >= 0
                    r.t = i;
                    while (i > 0) {
                        r[--i] = 0;
                    }
                    for (var j = r.t - this.t; i < j; ++i) {
                        r[i + this.t] = this.am(0, a[i], r, i, 0, this.t);
                    }
                    for (var j = Math.min(a.t, n); i < j; ++i) {
                        this.am(0, a[i], r, i, 0, n - i);
                    }
                    r.clamp();
                };
                // BigInteger.prototype.multiplyUpperTo = bnpMultiplyUpperTo;
                // (protected) r = "this * a" without lower n words, n > 0
                // "this" should be the larger one if appropriate.
                BigInteger.prototype.multiplyUpperTo = function (a, n, r) {
                    --n;
                    var i = r.t = this.t + a.t - n;
                    r.s = 0; // assumes a,this >= 0
                    while (--i >= 0) {
                        r[i] = 0;
                    }
                    for (i = Math.max(n - this.t, 0); i < a.t; ++i) {
                        r[this.t + i - n] = this.am(n - i, a[i], r, 0, 0, this.t + i - n);
                    }
                    r.clamp();
                    r.drShiftTo(1, r);
                };
                // BigInteger.prototype.modInt = bnpModInt;
                // (protected) this % n, n < 2^26
                BigInteger.prototype.modInt = function (n) {
                    if (n <= 0) {
                        return 0;
                    }
                    var d = this.DV % n;
                    var r = (this.s < 0) ? n - 1 : 0;
                    if (this.t > 0) {
                        if (d == 0) {
                            r = this[0] % n;
                        }
                        else {
                            for (var i = this.t - 1; i >= 0; --i) {
                                r = (d * r + this[i]) % n;
                            }
                        }
                    }
                    return r;
                };
                // BigInteger.prototype.millerRabin = bnpMillerRabin;
                // (protected) true if probably prime (HAC 4.24, Miller-Rabin)
                BigInteger.prototype.millerRabin = function (t) {
                    var n1 = this.subtract(BigInteger.ONE);
                    var k = n1.getLowestSetBit();
                    if (k <= 0) {
                        return false;
                    }
                    var r = n1.shiftRight(k);
                    t = (t + 1) >> 1;
                    if (t > lowprimes.length) {
                        t = lowprimes.length;
                    }
                    var a = nbi();
                    for (var i = 0; i < t; ++i) {
                        // Pick bases at random, instead of starting at 2
                        a.fromInt(lowprimes[Math.floor(Math.random() * lowprimes.length)]);
                        var y = a.modPow(r, this);
                        if (y.compareTo(BigInteger.ONE) != 0 && y.compareTo(n1) != 0) {
                            var j = 1;
                            while (j++ < k && y.compareTo(n1) != 0) {
                                y = y.modPowInt(2, this);
                                if (y.compareTo(BigInteger.ONE) == 0) {
                                    return false;
                                }
                            }
                            if (y.compareTo(n1) != 0) {
                                return false;
                            }
                        }
                    }
                    return true;
                };
                // BigInteger.prototype.square = bnSquare;
                // (public) this^2
                BigInteger.prototype.square = function () {
                    var r = nbi();
                    this.squareTo(r);
                    return r;
                };
                //#region ASYNC
                // Public API method
                BigInteger.prototype.gcda = function (a, callback) {
                    var x = (this.s < 0) ? this.negate() : this.clone();
                    var y = (a.s < 0) ? a.negate() : a.clone();
                    if (x.compareTo(y) < 0) {
                        var t = x;
                        x = y;
                        y = t;
                    }
                    var i = x.getLowestSetBit();
                    var g = y.getLowestSetBit();
                    if (g < 0) {
                        callback(x);
                        return;
                    }
                    if (i < g) {
                        g = i;
                    }
                    if (g > 0) {
                        x.rShiftTo(g, x);
                        y.rShiftTo(g, y);
                    }
                    // Workhorse of the algorithm, gets called 200 - 800 times per 512 bit keygen.
                    var gcda1 = function () {
                        if ((i = x.getLowestSetBit()) > 0) {
                            x.rShiftTo(i, x);
                        }
                        if ((i = y.getLowestSetBit()) > 0) {
                            y.rShiftTo(i, y);
                        }
                        if (x.compareTo(y) >= 0) {
                            x.subTo(y, x);
                            x.rShiftTo(1, x);
                        }
                        else {
                            y.subTo(x, y);
                            y.rShiftTo(1, y);
                        }
                        if (!(x.signum() > 0)) {
                            if (g > 0) {
                                y.lShiftTo(g, y);
                            }
                            setTimeout(function () { callback(y); }, 0); // escape
                        }
                        else {
                            setTimeout(gcda1, 0);
                        }
                    };
                    setTimeout(gcda1, 10);
                };
                // (protected) alternate constructor
                BigInteger.prototype.fromNumberAsync = function (a, b, c, callback) {
                    if ("number" == typeof b) {
                        if (a < 2) {
                            this.fromInt(1);
                        }
                        else {
                            this.fromNumber(a, c);
                            if (!this.testBit(a - 1)) {
                                this.bitwiseTo(BigInteger.ONE.shiftLeft(a - 1), op_or, this);
                            }
                            if (this.isEven()) {
                                this.dAddOffset(1, 0);
                            }
                            var bnp_1 = this;
                            var bnpfn1_1 = function () {
                                bnp_1.dAddOffset(2, 0);
                                if (bnp_1.bitLength() > a) {
                                    bnp_1.subTo(BigInteger.ONE.shiftLeft(a - 1), bnp_1);
                                }
                                if (bnp_1.isProbablePrime(b)) {
                                    setTimeout(function () { callback(); }, 0); // escape
                                }
                                else {
                                    setTimeout(bnpfn1_1, 0);
                                }
                            };
                            setTimeout(bnpfn1_1, 0);
                        }
                    }
                    else {
                        var x = [];
                        var t = a & 7;
                        x.length = (a >> 3) + 1;
                        b.nextBytes(x);
                        if (t > 0) {
                            x[0] &= ((1 << t) - 1);
                        }
                        else {
                            x[0] = 0;
                        }
                        this.fromString(x, 256);
                    }
                };
                return BigInteger;
            }());
            //#region REDUCERS
            //#region NullExp
            var NullExp = /** @class */ (function () {
                function NullExp() {
                }
                // NullExp.prototype.convert = nNop;
                NullExp.prototype.convert = function (x) {
                    return x;
                };
                // NullExp.prototype.revert = nNop;
                NullExp.prototype.revert = function (x) {
                    return x;
                };
                // NullExp.prototype.mulTo = nMulTo;
                NullExp.prototype.mulTo = function (x, y, r) {
                    x.multiplyTo(y, r);
                };
                // NullExp.prototype.sqrTo = nSqrTo;
                NullExp.prototype.sqrTo = function (x, r) {
                    x.squareTo(r);
                };
                return NullExp;
            }());
            // Modular reduction using "classic" algorithm
            var Classic = /** @class */ (function () {
                function Classic(m) {
                    this.m = m;
                }
                // Classic.prototype.convert = cConvert;
                Classic.prototype.convert = function (x) {
                    if (x.s < 0 || x.compareTo(this.m) >= 0) {
                        return x.mod(this.m);
                    }
                    else {
                        return x;
                    }
                };
                // Classic.prototype.revert = cRevert;
                Classic.prototype.revert = function (x) {
                    return x;
                };
                // Classic.prototype.reduce = cReduce;
                Classic.prototype.reduce = function (x) {
                    x.divRemTo(this.m, null, x);
                };
                // Classic.prototype.mulTo = cMulTo;
                Classic.prototype.mulTo = function (x, y, r) {
                    x.multiplyTo(y, r);
                    this.reduce(r);
                };
                // Classic.prototype.sqrTo = cSqrTo;
                Classic.prototype.sqrTo = function (x, r) {
                    x.squareTo(r);
                    this.reduce(r);
                };
                return Classic;
            }());
            //#endregion
            //#region Montgomery
            // Montgomery reduction
            var Montgomery = /** @class */ (function () {
                function Montgomery(m) {
                    this.m = m;
                    this.mp = m.invDigit();
                    this.mpl = this.mp & 0x7fff;
                    this.mph = this.mp >> 15;
                    this.um = (1 << (m.DB - 15)) - 1;
                    this.mt2 = 2 * m.t;
                }
                // Montgomery.prototype.convert = montConvert;
                // xR mod m
                Montgomery.prototype.convert = function (x) {
                    var r = nbi();
                    x.abs().dlShiftTo(this.m.t, r);
                    r.divRemTo(this.m, null, r);
                    if (x.s < 0 && r.compareTo(BigInteger.ZERO) > 0) {
                        this.m.subTo(r, r);
                    }
                    return r;
                };
                // Montgomery.prototype.revert = montRevert;
                // x/R mod m
                Montgomery.prototype.revert = function (x) {
                    var r = nbi();
                    x.copyTo(r);
                    this.reduce(r);
                    return r;
                };
                // Montgomery.prototype.reduce = montReduce;
                // x = x/R mod m (HAC 14.32)
                Montgomery.prototype.reduce = function (x) {
                    while (x.t <= this.mt2) {
                        // pad x so am has enough room later
                        x[x.t++] = 0;
                    }
                    for (var i = 0; i < this.m.t; ++i) {
                        // faster way of calculating u0 = x[i]*mp mod DV
                        var j = x[i] & 0x7fff;
                        var u0 = (j * this.mpl + (((j * this.mph + (x[i] >> 15) * this.mpl) & this.um) << 15)) & x.DM;
                        // use am to combine the multiply-shift-add into one call
                        j = i + this.m.t;
                        x[j] += this.m.am(0, u0, x, i, 0, this.m.t);
                        // propagate carry
                        while (x[j] >= x.DV) {
                            x[j] -= x.DV;
                            x[++j]++;
                        }
                    }
                    x.clamp();
                    x.drShiftTo(this.m.t, x);
                    if (x.compareTo(this.m) >= 0) {
                        x.subTo(this.m, x);
                    }
                };
                // Montgomery.prototype.mulTo = montMulTo;
                // r = "xy/R mod m"; x,y != r
                Montgomery.prototype.mulTo = function (x, y, r) {
                    x.multiplyTo(y, r);
                    this.reduce(r);
                };
                // Montgomery.prototype.sqrTo = montSqrTo;
                // r = "x^2/R mod m"; x != r
                Montgomery.prototype.sqrTo = function (x, r) {
                    x.squareTo(r);
                    this.reduce(r);
                };
                return Montgomery;
            }());
            //#endregion Montgomery
            //#region Barrett
            // Barrett modular reduction
            var Barrett = /** @class */ (function () {
                function Barrett(m) {
                    this.m = m;
                    // setup Barrett
                    this.r2 = nbi();
                    this.q3 = nbi();
                    BigInteger.ONE.dlShiftTo(2 * m.t, this.r2);
                    this.mu = this.r2.divide(m);
                }
                // Barrett.prototype.convert = barrettConvert;
                Barrett.prototype.convert = function (x) {
                    if (x.s < 0 || x.t > 2 * this.m.t) {
                        return x.mod(this.m);
                    }
                    else if (x.compareTo(this.m) < 0) {
                        return x;
                    }
                    else {
                        var r = nbi();
                        x.copyTo(r);
                        this.reduce(r);
                        return r;
                    }
                };
                // Barrett.prototype.revert = barrettRevert;
                Barrett.prototype.revert = function (x) {
                    return x;
                };
                // Barrett.prototype.reduce = barrettReduce;
                // x = x mod m (HAC 14.42)
                Barrett.prototype.reduce = function (x) {
                    x.drShiftTo(this.m.t - 1, this.r2);
                    if (x.t > this.m.t + 1) {
                        x.t = this.m.t + 1;
                        x.clamp();
                    }
                    this.mu.multiplyUpperTo(this.r2, this.m.t + 1, this.q3);
                    this.m.multiplyLowerTo(this.q3, this.m.t + 1, this.r2);
                    while (x.compareTo(this.r2) < 0) {
                        x.dAddOffset(1, this.m.t + 1);
                    }
                    x.subTo(this.r2, x);
                    while (x.compareTo(this.m) >= 0) {
                        x.subTo(this.m, x);
                    }
                };
                // Barrett.prototype.mulTo = barrettMulTo;
                // r = x*y mod m; x,y != r
                Barrett.prototype.mulTo = function (x, y, r) {
                    x.multiplyTo(y, r);
                    this.reduce(r);
                };
                // Barrett.prototype.sqrTo = barrettSqrTo;
                // r = x^2 mod m; x != r
                Barrett.prototype.sqrTo = function (x, r) {
                    x.squareTo(r);
                    this.reduce(r);
                };
                return Barrett;
            }());
            //#endregion
            //#endregion REDUCERS
            // return new, unset BigInteger
            function nbi() { return new BigInteger(null); }
            function parseBigInt(str, r) {
                return new BigInteger(str, r);
            }
            // am: Compute w_j += (x*this_i), propagate carries,
            // c is initial carry, returns final carry.
            // c < 3*dvalue, x < 2*dvalue, this_i < dvalue
            // We need to select the fastest one that works in this environment.
            // am1: use a single mult and divide to get the high bits,
            // max digit bits should be 26 because
            // max internal value = 2*dvalue^2-2*dvalue (< 2^53)
            function am1(i, x, w, j, c, n) {
                while (--n >= 0) {
                    var v = x * this[i++] + w[j] + c;
                    c = Math.floor(v / 0x4000000);
                    w[j++] = v & 0x3ffffff;
                }
                return c;
            }
            // am2 avoids a big mult-and-extract completely.
            // Max digit bits should be <= 30 because we do bitwise ops
            // on values up to 2*hdvalue^2-hdvalue-1 (< 2^31)
            function am2(i, x, w, j, c, n) {
                var xl = x & 0x7fff;
                var xh = x >> 15;
                while (--n >= 0) {
                    var l = this[i] & 0x7fff;
                    var h = this[i++] >> 15;
                    var m = xh * l + h * xl;
                    l = xl * l + ((m & 0x7fff) << 15) + w[j] + (c & 0x3fffffff);
                    c = (l >>> 30) + (m >>> 15) + xh * h + (c >>> 30);
                    w[j++] = l & 0x3fffffff;
                }
                return c;
            }
            // Alternately, set max digit bits to 28 since some
            // browsers slow down when dealing with 32-bit numbers.
            function am3(i, x, w, j, c, n) {
                var xl = x & 0x3fff;
                var xh = x >> 14;
                while (--n >= 0) {
                    var l = this[i] & 0x3fff;
                    var h = this[i++] >> 14;
                    var m = xh * l + h * xl;
                    l = xl * l + ((m & 0x3fff) << 14) + w[j] + c;
                    c = (l >> 28) + (m >> 14) + xh * h;
                    w[j++] = l & 0xfffffff;
                }
                return c;
            }
            if ((navigator.appName == "Microsoft Internet Explorer")) {
                BigInteger.prototype.am = am2;
                dbits = 30;
            }
            else if ((navigator.appName != "Netscape")) {
                BigInteger.prototype.am = am1;
                dbits = 26;
            }
            else { // Mozilla/Netscape seems to prefer am3
                BigInteger.prototype.am = am3;
                dbits = 28;
            }
            BigInteger.prototype.DB = dbits;
            BigInteger.prototype.DM = ((1 << dbits) - 1);
            BigInteger.prototype.DV = (1 << dbits);
            var BI_FP = 52;
            BigInteger.prototype.FV = Math.pow(2, BI_FP);
            BigInteger.prototype.F1 = BI_FP - dbits;
            BigInteger.prototype.F2 = 2 * dbits - BI_FP;
            // Digit conversions
            var BI_RC = [];
            var rr;
            var vv;
            rr = "0".charCodeAt(0);
            for (vv = 0; vv <= 9; ++vv) {
                BI_RC[rr++] = vv;
            }
            rr = "a".charCodeAt(0);
            for (vv = 10; vv < 36; ++vv) {
                BI_RC[rr++] = vv;
            }
            rr = "A".charCodeAt(0);
            for (vv = 10; vv < 36; ++vv) {
                BI_RC[rr++] = vv;
            }
            function intAt(s, i) {
                var c = BI_RC[s.charCodeAt(i)];
                return (c == null) ? -1 : c;
            }
            // return bigint initialized to value
            function nbv(i) {
                var r = nbi();
                r.fromInt(i);
                return r;
            }
            // returns bit length of the integer x
            function nbits(x) {
                var r = 1;
                var t;
                if ((t = x >>> 16) != 0) {
                    x = t;
                    r += 16;
                }
                if ((t = x >> 8) != 0) {
                    x = t;
                    r += 8;
                }
                if ((t = x >> 4) != 0) {
                    x = t;
                    r += 4;
                }
                if ((t = x >> 2) != 0) {
                    x = t;
                    r += 2;
                }
                if ((t = x >> 1) != 0) {
                    x = t;
                    r += 1;
                }
                return r;
            }
            // "constants"
            BigInteger.ZERO = nbv(0);
            BigInteger.ONE = nbv(1);

            // prng4.js - uses Arcfour as a PRNG
            var Arcfour = /** @class */ (function () {
                function Arcfour() {
                    this.i = 0;
                    this.j = 0;
                    this.S = [];
                }
                // Arcfour.prototype.init = ARC4init;
                // Initialize arcfour context from key, an array of ints, each from [0..255]
                Arcfour.prototype.init = function (key) {
                    var i;
                    var j;
                    var t;
                    for (i = 0; i < 256; ++i) {
                        this.S[i] = i;
                    }
                    j = 0;
                    for (i = 0; i < 256; ++i) {
                        j = (j + this.S[i] + key[i % key.length]) & 255;
                        t = this.S[i];
                        this.S[i] = this.S[j];
                        this.S[j] = t;
                    }
                    this.i = 0;
                    this.j = 0;
                };
                // Arcfour.prototype.next = ARC4next;
                Arcfour.prototype.next = function () {
                    var t;
                    this.i = (this.i + 1) & 255;
                    this.j = (this.j + this.S[this.i]) & 255;
                    t = this.S[this.i];
                    this.S[this.i] = this.S[this.j];
                    this.S[this.j] = t;
                    return this.S[(t + this.S[this.i]) & 255];
                };
                return Arcfour;
            }());
            // Plug in your RNG constructor here
            function prng_newstate() {
                return new Arcfour();
            }
            // Pool size must be a multiple of 4 and greater than 32.
            // An array of bytes the size of the pool will be passed to init()
            var rng_psize = 256;

            // Random number generator - requires a PRNG backend, e.g. prng4.js
            var rng_state;
            var rng_pool = null;
            var rng_pptr;
            // Initialize the pool with junk if needed.
            if (rng_pool == null) {
                rng_pool = [];
                rng_pptr = 0;
                var t = void 0;
                if (window.crypto && window.crypto.getRandomValues) {
                    // Extract entropy (2048 bits) from RNG if available
                    var z = new Uint32Array(256);
                    window.crypto.getRandomValues(z);
                    for (t = 0; t < z.length; ++t) {
                        rng_pool[rng_pptr++] = z[t] & 255;
                    }
                }
                // Use mouse events for entropy, if we do not have enough entropy by the time
                // we need it, entropy will be generated by Math.random.
                var onMouseMoveListener_1 = function (ev) {
                    this.count = this.count || 0;
                    if (this.count >= 256 || rng_pptr >= rng_psize) {
                        if (window.removeEventListener) {
                            window.removeEventListener("mousemove", onMouseMoveListener_1, false);
                        }
                        else if (window.detachEvent) {
                            window.detachEvent("onmousemove", onMouseMoveListener_1);
                        }
                        return;
                    }
                    try {
                        var mouseCoordinates = ev.x + ev.y;
                        rng_pool[rng_pptr++] = mouseCoordinates & 255;
                        this.count += 1;
                    }
                    catch (e) {
                        // Sometimes Firefox will deny permission to access event properties for some reason. Ignore.
                    }
                };
                if (window.addEventListener) {
                    window.addEventListener("mousemove", onMouseMoveListener_1, false);
                }
                else if (window.attachEvent) {
                    window.attachEvent("onmousemove", onMouseMoveListener_1);
                }
            }
            function rng_get_byte() {
                if (rng_state == null) {
                    rng_state = prng_newstate();
                    // At this point, we may not have collected enough entropy.  If not, fall back to Math.random
                    while (rng_pptr < rng_psize) {
                        var random = Math.floor(65536 * Math.random());
                        rng_pool[rng_pptr++] = random & 255;
                    }
                    rng_state.init(rng_pool);
                    for (rng_pptr = 0; rng_pptr < rng_pool.length; ++rng_pptr) {
                        rng_pool[rng_pptr] = 0;
                    }
                    rng_pptr = 0;
                }
                // TODO: allow reseeding after first request
                return rng_state.next();
            }
            var SecureRandom = /** @class */ (function () {
                function SecureRandom() {
                }
                SecureRandom.prototype.nextBytes = function (ba) {
                    for (var i = 0; i < ba.length; ++i) {
                        ba[i] = rng_get_byte();
                    }
                };
                return SecureRandom;
            }());

            // Depends on jsbn.js and rng.js
            // function linebrk(s,n) {
            //   var ret = "";
            //   var i = 0;
            //   while(i + n < s.length) {
            //     ret += s.substring(i,i+n) + "\n";
            //     i += n;
            //   }
            //   return ret + s.substring(i,s.length);
            // }
            // function byte2Hex(b) {
            //   if(b < 0x10)
            //     return "0" + b.toString(16);
            //   else
            //     return b.toString(16);
            // }
            function pkcs1pad1(s, n) {
                if (n < s.length + 22) {
                    console.error("Message too long for RSA");
                    return null;
                }
                var len = n - s.length - 6;
                var filler = "";
                for (var f = 0; f < len; f += 2) {
                    filler += "ff";
                }
                var m = "0001" + filler + "00" + s;
                return parseBigInt(m, 16);
            }
            // PKCS#1 (type 2, random) pad input string s to n bytes, and return a bigint
            function pkcs1pad2(s, n) {
                if (n < s.length + 11) { // TODO: fix for utf-8
                    console.error("Message too long for RSA");
                    return null;
                }
                var ba = [];
                var i = s.length - 1;
                while (i >= 0 && n > 0) {
                    var c = s.charCodeAt(i--);
                    if (c < 128) { // encode using utf-8
                        ba[--n] = c;
                    }
                    else if ((c > 127) && (c < 2048)) {
                        ba[--n] = (c & 63) | 128;
                        ba[--n] = (c >> 6) | 192;
                    }
                    else {
                        ba[--n] = (c & 63) | 128;
                        ba[--n] = ((c >> 6) & 63) | 128;
                        ba[--n] = (c >> 12) | 224;
                    }
                }
                ba[--n] = 0;
                var rng = new SecureRandom();
                var x = [];
                while (n > 2) { // random non-zero pad
                    x[0] = 0;
                    while (x[0] == 0) {
                        rng.nextBytes(x);
                    }
                    ba[--n] = x[0];
                }
                ba[--n] = 2;
                ba[--n] = 0;
                return new BigInteger(ba);
            }
            // "empty" RSA key constructor
            var RSAKey = /** @class */ (function () {
                function RSAKey() {
                    this.n = null;
                    this.e = 0;
                    this.d = null;
                    this.p = null;
                    this.q = null;
                    this.dmp1 = null;
                    this.dmq1 = null;
                    this.coeff = null;
                }
                //#region PROTECTED
                // protected
                // RSAKey.prototype.doPublic = RSADoPublic;
                // Perform raw public operation on "x": return x^e (mod n)
                RSAKey.prototype.doPublic = function (x) {
                    return x.modPowInt(this.e, this.n);
                };
                // RSAKey.prototype.doPrivate = RSADoPrivate;
                // Perform raw private operation on "x": return x^d (mod n)
                RSAKey.prototype.doPrivate = function (x) {
                    if (this.p == null || this.q == null) {
                        return x.modPow(this.d, this.n);
                    }
                    // TODO: re-calculate any missing CRT params
                    var xp = x.mod(this.p).modPow(this.dmp1, this.p);
                    var xq = x.mod(this.q).modPow(this.dmq1, this.q);
                    while (xp.compareTo(xq) < 0) {
                        xp = xp.add(this.p);
                    }
                    return xp.subtract(xq).multiply(this.coeff).mod(this.p).multiply(this.q).add(xq);
                };
                //#endregion PROTECTED
                //#region PUBLIC
                // RSAKey.prototype.setPublic = RSASetPublic;
                // Set the public key fields N and e from hex strings
                RSAKey.prototype.setPublic = function (N, E) {
                    if (N != null && E != null && N.length > 0 && E.length > 0) {
                        this.n = parseBigInt(N, 16);
                        this.e = parseInt(E, 16);
                    }
                    else {
                        console.error("Invalid RSA public key");
                    }
                };
                // RSAKey.prototype.encrypt = RSAEncrypt;
                // Return the PKCS#1 RSA encryption of "text" as an even-length hex string
                RSAKey.prototype.encrypt = function (text) {
                    var m = pkcs1pad2(text, (this.n.bitLength() + 7) >> 3);
                    if (m == null) {
                        return null;
                    }
                    var c = this.doPublic(m);
                    if (c == null) {
                        return null;
                    }
                    var h = c.toString(16);
                    if ((h.length & 1) == 0) {
                        return h;
                    }
                    else {
                        return "0" + h;
                    }
                };
                // RSAKey.prototype.setPrivate = RSASetPrivate;
                // Set the private key fields N, e, and d from hex strings
                RSAKey.prototype.setPrivate = function (N, E, D) {
                    if (N != null && E != null && N.length > 0 && E.length > 0) {
                        this.n = parseBigInt(N, 16);
                        this.e = parseInt(E, 16);
                        this.d = parseBigInt(D, 16);
                    }
                    else {
                        console.error("Invalid RSA private key");
                    }
                };
                // RSAKey.prototype.setPrivateEx = RSASetPrivateEx;
                // Set the private key fields N, e, d and CRT params from hex strings
                RSAKey.prototype.setPrivateEx = function (N, E, D, P, Q, DP, DQ, C) {
                    if (N != null && E != null && N.length > 0 && E.length > 0) {
                        this.n = parseBigInt(N, 16);
                        this.e = parseInt(E, 16);
                        this.d = parseBigInt(D, 16);
                        this.p = parseBigInt(P, 16);
                        this.q = parseBigInt(Q, 16);
                        this.dmp1 = parseBigInt(DP, 16);
                        this.dmq1 = parseBigInt(DQ, 16);
                        this.coeff = parseBigInt(C, 16);
                    }
                    else {
                        console.error("Invalid RSA private key");
                    }
                };
                // RSAKey.prototype.generate = RSAGenerate;
                // Generate a new random private key B bits long, using public expt E
                RSAKey.prototype.generate = function (B, E) {
                    var rng = new SecureRandom();
                    var qs = B >> 1;
                    this.e = parseInt(E, 16);
                    var ee = new BigInteger(E, 16);
                    for (;;) {
                        for (;;) {
                            this.p = new BigInteger(B - qs, 1, rng);
                            if (this.p.subtract(BigInteger.ONE).gcd(ee).compareTo(BigInteger.ONE) == 0 && this.p.isProbablePrime(10)) {
                                break;
                            }
                        }
                        for (;;) {
                            this.q = new BigInteger(qs, 1, rng);
                            if (this.q.subtract(BigInteger.ONE).gcd(ee).compareTo(BigInteger.ONE) == 0 && this.q.isProbablePrime(10)) {
                                break;
                            }
                        }
                        if (this.p.compareTo(this.q) <= 0) {
                            var t = this.p;
                            this.p = this.q;
                            this.q = t;
                        }
                        var p1 = this.p.subtract(BigInteger.ONE);
                        var q1 = this.q.subtract(BigInteger.ONE);
                        var phi = p1.multiply(q1);
                        if (phi.gcd(ee).compareTo(BigInteger.ONE) == 0) {
                            this.n = this.p.multiply(this.q);
                            this.d = ee.modInverse(phi);
                            this.dmp1 = this.d.mod(p1);
                            this.dmq1 = this.d.mod(q1);
                            this.coeff = this.q.modInverse(this.p);
                            break;
                        }
                    }
                };
                // RSAKey.prototype.decrypt = RSADecrypt;
                // Return the PKCS#1 RSA decryption of "ctext".
                // "ctext" is an even-length hex string and the output is a plain string.
                RSAKey.prototype.decrypt = function (ctext) {
                    var c = parseBigInt(ctext, 16);
                    var m = this.doPrivate(c);
                    if (m == null) {
                        return null;
                    }
                    return pkcs1unpad2(m, (this.n.bitLength() + 7) >> 3);
                };
                // Generate a new random private key B bits long, using public expt E
                RSAKey.prototype.generateAsync = function (B, E, callback) {
                    var rng = new SecureRandom();
                    var qs = B >> 1;
                    this.e = parseInt(E, 16);
                    var ee = new BigInteger(E, 16);
                    var rsa = this;
                    // These functions have non-descript names because they were originally for(;;) loops.
                    // I don't know about cryptography to give them better names than loop1-4.
                    var loop1 = function () {
                        var loop4 = function () {
                            if (rsa.p.compareTo(rsa.q) <= 0) {
                                var t = rsa.p;
                                rsa.p = rsa.q;
                                rsa.q = t;
                            }
                            var p1 = rsa.p.subtract(BigInteger.ONE);
                            var q1 = rsa.q.subtract(BigInteger.ONE);
                            var phi = p1.multiply(q1);
                            if (phi.gcd(ee).compareTo(BigInteger.ONE) == 0) {
                                rsa.n = rsa.p.multiply(rsa.q);
                                rsa.d = ee.modInverse(phi);
                                rsa.dmp1 = rsa.d.mod(p1);
                                rsa.dmq1 = rsa.d.mod(q1);
                                rsa.coeff = rsa.q.modInverse(rsa.p);
                                setTimeout(function () { callback(); }, 0); // escape
                            }
                            else {
                                setTimeout(loop1, 0);
                            }
                        };
                        var loop3 = function () {
                            rsa.q = nbi();
                            rsa.q.fromNumberAsync(qs, 1, rng, function () {
                                rsa.q.subtract(BigInteger.ONE).gcda(ee, function (r) {
                                    if (r.compareTo(BigInteger.ONE) == 0 && rsa.q.isProbablePrime(10)) {
                                        setTimeout(loop4, 0);
                                    }
                                    else {
                                        setTimeout(loop3, 0);
                                    }
                                });
                            });
                        };
                        var loop2 = function () {
                            rsa.p = nbi();
                            rsa.p.fromNumberAsync(B - qs, 1, rng, function () {
                                rsa.p.subtract(BigInteger.ONE).gcda(ee, function (r) {
                                    if (r.compareTo(BigInteger.ONE) == 0 && rsa.p.isProbablePrime(10)) {
                                        setTimeout(loop3, 0);
                                    }
                                    else {
                                        setTimeout(loop2, 0);
                                    }
                                });
                            });
                        };
                        setTimeout(loop2, 0);
                    };
                    setTimeout(loop1, 0);
                };
                RSAKey.prototype.sign = function (text, digestMethod, digestName) {
                    var header = getDigestHeader(digestName);
                    var digest = header + digestMethod(text).toString();
                    var m = pkcs1pad1(digest, this.n.bitLength() / 4);
                    if (m == null) {
                        return null;
                    }
                    var c = this.doPrivate(m);
                    if (c == null) {
                        return null;
                    }
                    var h = c.toString(16);
                    if ((h.length & 1) == 0) {
                        return h;
                    }
                    else {
                        return "0" + h;
                    }
                };
                RSAKey.prototype.verify = function (text, signature, digestMethod) {
                    var c = parseBigInt(signature, 16);
                    var m = this.doPublic(c);
                    if (m == null) {
                        return null;
                    }
                    var unpadded = m.toString(16).replace(/^1f+00/, "");
                    var digest = removeDigestHeader(unpadded);
                    return digest == digestMethod(text).toString();
                };
                return RSAKey;
            }());
            // Undo PKCS#1 (type 2, random) padding and, if valid, return the plaintext
            function pkcs1unpad2(d, n) {
                var b = d.toByteArray();
                var i = 0;
                while (i < b.length && b[i] == 0) {
                    ++i;
                }
                if (b.length - i != n - 1 || b[i] != 2) {
                    return null;
                }
                ++i;
                while (b[i] != 0) {
                    if (++i >= b.length) {
                        return null;
                    }
                }
                var ret = "";
                while (++i < b.length) {
                    var c = b[i] & 255;
                    if (c < 128) { // utf-8 decode
                        ret += String.fromCharCode(c);
                    }
                    else if ((c > 191) && (c < 224)) {
                        ret += String.fromCharCode(((c & 31) << 6) | (b[i + 1] & 63));
                        ++i;
                    }
                    else {
                        ret += String.fromCharCode(((c & 15) << 12) | ((b[i + 1] & 63) << 6) | (b[i + 2] & 63));
                        i += 2;
                    }
                }
                return ret;
            }
            // https://tools.ietf.org/html/rfc3447#page-43
            var DIGEST_HEADERS = {
                md2: "3020300c06082a864886f70d020205000410",
                md5: "3020300c06082a864886f70d020505000410",
                sha1: "3021300906052b0e03021a05000414",
                sha224: "302d300d06096086480165030402040500041c",
                sha256: "3031300d060960864801650304020105000420",
                sha384: "3041300d060960864801650304020205000430",
                sha512: "3051300d060960864801650304020305000440",
                ripemd160: "3021300906052b2403020105000414",
            };
            function getDigestHeader(name) {
                return DIGEST_HEADERS[name] || "";
            }
            function removeDigestHeader(str) {
                for (var name_1 in DIGEST_HEADERS) {
                    if (DIGEST_HEADERS.hasOwnProperty(name_1)) {
                        var header = DIGEST_HEADERS[name_1];
                        var len = header.length;
                        if (str.substr(0, len) == header) {
                            return str.substr(len);
                        }
                    }
                }
                return str;
            }
            // Return the PKCS#1 RSA encryption of "text" as a Base64-encoded string
            // function RSAEncryptB64(text) {
            //  var h = this.encrypt(text);
            //  if(h) return hex2b64(h); else return null;
            // }
            // public
            // RSAKey.prototype.encrypt_b64 = RSAEncryptB64;

            /*!
    Copyright (c) 2011, Yahoo! Inc. All rights reserved.
    Code licensed under the BSD License:
    http://developer.yahoo.com/yui/license.html
    version: 2.9.0
    */
            var YAHOO = {};
            YAHOO.lang = {
                /**
                 * Utility to set up the prototype, constructor and superclass properties to
                 * support an inheritance strategy that can chain constructors and methods.
                 * Static members will not be inherited.
                 *
                 * @method extend
                 * @static
                 * @param {Function} subc   the object to modify
                 * @param {Function} superc the object to inherit
                 * @param {Object} overrides  additional properties/methods to add to the
                 *                              subclass prototype.  These will override the
                 *                              matching items obtained from the superclass
                 *                              if present.
                 */
                extend: function(subc, superc, overrides) {
                    if (! superc || ! subc) {
                        throw new Error("YAHOO.lang.extend failed, please check that " +
                            "all dependencies are included.");
                    }

                    var F = function() {};
                    F.prototype = superc.prototype;
                    subc.prototype = new F();
                    subc.prototype.constructor = subc;
                    subc.superclass = superc.prototype;

                    if (superc.prototype.constructor == Object.prototype.constructor) {
                        superc.prototype.constructor = superc;
                    }

                    if (overrides) {
                        var i;
                        for (i in overrides) {
                            subc.prototype[i] = overrides[i];
                        }

                        /*
                 * IE will not enumerate native functions in a derived object even if the
                 * function was overridden.  This is a workaround for specific functions
                 * we care about on the Object prototype.
                 * @property _IEEnumFix
                 * @param {Function} r  the object to receive the augmentation
                 * @param {Function} s  the object that supplies the properties to augment
                 * @static
                 * @private
                 */
                        var _IEEnumFix = function() {},
                            ADD = ["toString", "valueOf"];
                        try {
                            if (/MSIE/.test(navigator.userAgent)) {
                                _IEEnumFix = function(r, s) {
                                    for (i = 0; i < ADD.length; i = i + 1) {
                                        var fname = ADD[i], f = s[fname];
                                        if (typeof f === 'function' && f != Object.prototype[fname]) {
                                            r[fname] = f;
                                        }
                                    }
                                };
                            }
                        } catch (ex) {}            _IEEnumFix(subc.prototype, overrides);
                    }
                }
            };

            /* asn1-1.0.13.js (c) 2013-2017 Kenji Urushima | kjur.github.com/jsrsasign/license
     */

            /**
             * @fileOverview
             * @name asn1-1.0.js
             * @author Kenji Urushima kenji.urushima@gmail.com
             * @version asn1 1.0.13 (2017-Jun-02)
             * @since jsrsasign 2.1
             * @license <a href="https://kjur.github.io/jsrsasign/license/">MIT License</a>
             */

            /**
             * kjur's class library name space
             * <p>
             * This name space provides following name spaces:
             * <ul>
             * <li>{@link KJUR.asn1} - ASN.1 primitive hexadecimal encoder</li>
             * <li>{@link KJUR.asn1.x509} - ASN.1 structure for X.509 certificate and CRL</li>
             * <li>{@link KJUR.crypto} - Java Cryptographic Extension(JCE) style MessageDigest/Signature
             * class and utilities</li>
             * </ul>
             * </p>
             * NOTE: Please ignore method summary and document of this namespace. This caused by a bug of jsdoc2.
             * @name KJUR
             * @namespace kjur's class library name space
             */
            var KJUR = {};

            /**
             * kjur's ASN.1 class library name space
             * <p>
             * This is ITU-T X.690 ASN.1 DER encoder class library and
             * class structure and methods is very similar to
             * org.bouncycastle.asn1 package of
             * well known BouncyCaslte Cryptography Library.
             * <h4>PROVIDING ASN.1 PRIMITIVES</h4>
             * Here are ASN.1 DER primitive classes.
             * <ul>
             * <li>0x01 {@link KJUR.asn1.DERBoolean}</li>
             * <li>0x02 {@link KJUR.asn1.DERInteger}</li>
             * <li>0x03 {@link KJUR.asn1.DERBitString}</li>
             * <li>0x04 {@link KJUR.asn1.DEROctetString}</li>
             * <li>0x05 {@link KJUR.asn1.DERNull}</li>
             * <li>0x06 {@link KJUR.asn1.DERObjectIdentifier}</li>
             * <li>0x0a {@link KJUR.asn1.DEREnumerated}</li>
             * <li>0x0c {@link KJUR.asn1.DERUTF8String}</li>
             * <li>0x12 {@link KJUR.asn1.DERNumericString}</li>
             * <li>0x13 {@link KJUR.asn1.DERPrintableString}</li>
             * <li>0x14 {@link KJUR.asn1.DERTeletexString}</li>
             * <li>0x16 {@link KJUR.asn1.DERIA5String}</li>
             * <li>0x17 {@link KJUR.asn1.DERUTCTime}</li>
             * <li>0x18 {@link KJUR.asn1.DERGeneralizedTime}</li>
             * <li>0x30 {@link KJUR.asn1.DERSequence}</li>
             * <li>0x31 {@link KJUR.asn1.DERSet}</li>
             * </ul>
             * <h4>OTHER ASN.1 CLASSES</h4>
             * <ul>
             * <li>{@link KJUR.asn1.ASN1Object}</li>
             * <li>{@link KJUR.asn1.DERAbstractString}</li>
             * <li>{@link KJUR.asn1.DERAbstractTime}</li>
             * <li>{@link KJUR.asn1.DERAbstractStructured}</li>
             * <li>{@link KJUR.asn1.DERTaggedObject}</li>
             * </ul>
             * <h4>SUB NAME SPACES</h4>
             * <ul>
             * <li>{@link KJUR.asn1.cades} - CAdES long term signature format</li>
             * <li>{@link KJUR.asn1.cms} - Cryptographic Message Syntax</li>
             * <li>{@link KJUR.asn1.csr} - Certificate Signing Request (CSR/PKCS#10)</li>
             * <li>{@link KJUR.asn1.tsp} - RFC 3161 Timestamping Protocol Format</li>
             * <li>{@link KJUR.asn1.x509} - RFC 5280 X.509 certificate and CRL</li>
             * </ul>
             * </p>
             * NOTE: Please ignore method summary and document of this namespace.
             * This caused by a bug of jsdoc2.
             * @name KJUR.asn1
             * @namespace
             */
            if (typeof KJUR.asn1 == "undefined" || !KJUR.asn1) KJUR.asn1 = {};

            /**
             * ASN1 utilities class
             * @name KJUR.asn1.ASN1Util
             * @class ASN1 utilities class
             * @since asn1 1.0.2
             */
            KJUR.asn1.ASN1Util = new function() {
                this.integerToByteHex = function(i) {
                    var h = i.toString(16);
                    if ((h.length % 2) == 1) h = '0' + h;
                    return h;
                };
                this.bigIntToMinTwosComplementsHex = function(bigIntegerValue) {
                    var h = bigIntegerValue.toString(16);
                    if (h.substr(0, 1) != '-') {
                        if (h.length % 2 == 1) {
                            h = '0' + h;
                        } else {
                            if (! h.match(/^[0-7]/)) {
                                h = '00' + h;
                            }
                        }
                    } else {
                        var hPos = h.substr(1);
                        var xorLen = hPos.length;
                        if (xorLen % 2 == 1) {
                            xorLen += 1;
                        } else {
                            if (! h.match(/^[0-7]/)) {
                                xorLen += 2;
                            }
                        }
                        var hMask = '';
                        for (var i = 0; i < xorLen; i++) {
                            hMask += 'f';
                        }
                        var biMask = new BigInteger(hMask, 16);
                        var biNeg = biMask.xor(bigIntegerValue).add(BigInteger.ONE);
                        h = biNeg.toString(16).replace(/^-/, '');
                    }
                    return h;
                };
                /**
                 * get PEM string from hexadecimal data and header string
                 * @name getPEMStringFromHex
                 * @memberOf KJUR.asn1.ASN1Util
                 * @function
                 * @param {String} dataHex hexadecimal string of PEM body
                 * @param {String} pemHeader PEM header string (ex. 'RSA PRIVATE KEY')
                 * @return {String} PEM formatted string of input data
                 * @description
                 * This method converts a hexadecimal string to a PEM string with
                 * a specified header. Its line break will be CRLF("\r\n").
                 * @example
                 * var pem  = KJUR.asn1.ASN1Util.getPEMStringFromHex('616161', 'RSA PRIVATE KEY');
                 * // value of pem will be:
                 * -----BEGIN PRIVATE KEY-----
                 * YWFh
                 * -----END PRIVATE KEY-----
                 */
                this.getPEMStringFromHex = function(dataHex, pemHeader) {
                    return hextopem(dataHex, pemHeader);
                };

                /**
                 * generate ASN1Object specifed by JSON parameters
                 * @name newObject
                 * @memberOf KJUR.asn1.ASN1Util
                 * @function
                 * @param {Array} param JSON parameter to generate ASN1Object
                 * @return {KJUR.asn1.ASN1Object} generated object
                 * @since asn1 1.0.3
                 * @description
                 * generate any ASN1Object specified by JSON param
                 * including ASN.1 primitive or structured.
                 * Generally 'param' can be described as follows:
                 * <blockquote>
                 * {TYPE-OF-ASNOBJ: ASN1OBJ-PARAMETER}
                 * </blockquote>
                 * 'TYPE-OF-ASN1OBJ' can be one of following symbols:
                 * <ul>
                 * <li>'bool' - DERBoolean</li>
                 * <li>'int' - DERInteger</li>
                 * <li>'bitstr' - DERBitString</li>
                 * <li>'octstr' - DEROctetString</li>
                 * <li>'null' - DERNull</li>
                 * <li>'oid' - DERObjectIdentifier</li>
                 * <li>'enum' - DEREnumerated</li>
                 * <li>'utf8str' - DERUTF8String</li>
                 * <li>'numstr' - DERNumericString</li>
                 * <li>'prnstr' - DERPrintableString</li>
                 * <li>'telstr' - DERTeletexString</li>
                 * <li>'ia5str' - DERIA5String</li>
                 * <li>'utctime' - DERUTCTime</li>
                 * <li>'gentime' - DERGeneralizedTime</li>
                 * <li>'seq' - DERSequence</li>
                 * <li>'set' - DERSet</li>
                 * <li>'tag' - DERTaggedObject</li>
                 * </ul>
                 * @example
                 * newObject({'prnstr': 'aaa'});
                 * newObject({'seq': [{'int': 3}, {'prnstr': 'aaa'}]})
                 * // ASN.1 Tagged Object
                 * newObject({'tag': {'tag': 'a1',
                 *                    'explicit': true,
                 *                    'obj': {'seq': [{'int': 3}, {'prnstr': 'aaa'}]}}});
                 * // more simple representation of ASN.1 Tagged Object
                 * newObject({'tag': ['a1',
                 *                    true,
                 *                    {'seq': [
                 *                      {'int': 3},
                 *                      {'prnstr': 'aaa'}]}
                 *                   ]});
                 */
                this.newObject = function(param) {
                    var _KJUR = KJUR,
                        _KJUR_asn1 = _KJUR.asn1,
                        _DERBoolean = _KJUR_asn1.DERBoolean,
                        _DERInteger = _KJUR_asn1.DERInteger,
                        _DERBitString = _KJUR_asn1.DERBitString,
                        _DEROctetString = _KJUR_asn1.DEROctetString,
                        _DERNull = _KJUR_asn1.DERNull,
                        _DERObjectIdentifier = _KJUR_asn1.DERObjectIdentifier,
                        _DEREnumerated = _KJUR_asn1.DEREnumerated,
                        _DERUTF8String = _KJUR_asn1.DERUTF8String,
                        _DERNumericString = _KJUR_asn1.DERNumericString,
                        _DERPrintableString = _KJUR_asn1.DERPrintableString,
                        _DERTeletexString = _KJUR_asn1.DERTeletexString,
                        _DERIA5String = _KJUR_asn1.DERIA5String,
                        _DERUTCTime = _KJUR_asn1.DERUTCTime,
                        _DERGeneralizedTime = _KJUR_asn1.DERGeneralizedTime,
                        _DERSequence = _KJUR_asn1.DERSequence,
                        _DERSet = _KJUR_asn1.DERSet,
                        _DERTaggedObject = _KJUR_asn1.DERTaggedObject,
                        _newObject = _KJUR_asn1.ASN1Util.newObject;

                    var keys = Object.keys(param);
                    if (keys.length != 1)
                        throw "key of param shall be only one.";
                    var key = keys[0];

                    if (":bool:int:bitstr:octstr:null:oid:enum:utf8str:numstr:prnstr:telstr:ia5str:utctime:gentime:seq:set:tag:".indexOf(":" + key + ":") == -1)
                        throw "undefined key: " + key;

                    if (key == "bool")    return new _DERBoolean(param[key]);
                    if (key == "int")     return new _DERInteger(param[key]);
                    if (key == "bitstr")  return new _DERBitString(param[key]);
                    if (key == "octstr")  return new _DEROctetString(param[key]);
                    if (key == "null")    return new _DERNull(param[key]);
                    if (key == "oid")     return new _DERObjectIdentifier(param[key]);
                    if (key == "enum")    return new _DEREnumerated(param[key]);
                    if (key == "utf8str") return new _DERUTF8String(param[key]);
                    if (key == "numstr")  return new _DERNumericString(param[key]);
                    if (key == "prnstr")  return new _DERPrintableString(param[key]);
                    if (key == "telstr")  return new _DERTeletexString(param[key]);
                    if (key == "ia5str")  return new _DERIA5String(param[key]);
                    if (key == "utctime") return new _DERUTCTime(param[key]);
                    if (key == "gentime") return new _DERGeneralizedTime(param[key]);

                    if (key == "seq") {
                        var paramList = param[key];
                        var a = [];
                        for (var i = 0; i < paramList.length; i++) {
                            var asn1Obj = _newObject(paramList[i]);
                            a.push(asn1Obj);
                        }
                        return new _DERSequence({'array': a});
                    }

                    if (key == "set") {
                        var paramList = param[key];
                        var a = [];
                        for (var i = 0; i < paramList.length; i++) {
                            var asn1Obj = _newObject(paramList[i]);
                            a.push(asn1Obj);
                        }
                        return new _DERSet({'array': a});
                    }

                    if (key == "tag") {
                        var tagParam = param[key];
                        if (Object.prototype.toString.call(tagParam) === '[object Array]' &&
                            tagParam.length == 3) {
                            var obj = _newObject(tagParam[2]);
                            return new _DERTaggedObject({tag: tagParam[0],
                                explicit: tagParam[1],
                                obj: obj});
                        } else {
                            var newParam = {};
                            if (tagParam.explicit !== undefined)
                                newParam.explicit = tagParam.explicit;
                            if (tagParam.tag !== undefined)
                                newParam.tag = tagParam.tag;
                            if (tagParam.obj === undefined)
                                throw "obj shall be specified for 'tag'.";
                            newParam.obj = _newObject(tagParam.obj);
                            return new _DERTaggedObject(newParam);
                        }
                    }
                };

                /**
                 * get encoded hexadecimal string of ASN1Object specifed by JSON parameters
                 * @name jsonToASN1HEX
                 * @memberOf KJUR.asn1.ASN1Util
                 * @function
                 * @param {Array} param JSON parameter to generate ASN1Object
                 * @return hexadecimal string of ASN1Object
                 * @since asn1 1.0.4
                 * @description
                 * As for ASN.1 object representation of JSON object,
                 * please see {@link newObject}.
                 * @example
                 * jsonToASN1HEX({'prnstr': 'aaa'});
                 */
                this.jsonToASN1HEX = function(param) {
                    var asn1Obj = this.newObject(param);
                    return asn1Obj.getEncodedHex();
                };
            };

            /**
             * get dot noted oid number string from hexadecimal value of OID
             * @name oidHexToInt
             * @memberOf KJUR.asn1.ASN1Util
             * @function
             * @param {String} hex hexadecimal value of object identifier
             * @return {String} dot noted string of object identifier
             * @since jsrsasign 4.8.3 asn1 1.0.7
             * @description
             * This static method converts from hexadecimal string representation of
             * ASN.1 value of object identifier to oid number string.
             * @example
             * KJUR.asn1.ASN1Util.oidHexToInt('550406') &rarr; "2.5.4.6"
             */
            KJUR.asn1.ASN1Util.oidHexToInt = function(hex) {
                var s = "";
                var i01 = parseInt(hex.substr(0, 2), 16);
                var i0 = Math.floor(i01 / 40);
                var i1 = i01 % 40;
                var s = i0 + "." + i1;

                var binbuf = "";
                for (var i = 2; i < hex.length; i += 2) {
                    var value = parseInt(hex.substr(i, 2), 16);
                    var bin = ("00000000" + value.toString(2)).slice(- 8);
                    binbuf = binbuf + bin.substr(1, 7);
                    if (bin.substr(0, 1) == "0") {
                        var bi = new BigInteger(binbuf, 2);
                        s = s + "." + bi.toString(10);
                        binbuf = "";
                    }
                }
                return s;
            };

            /**
             * get hexadecimal value of object identifier from dot noted oid value
             * @name oidIntToHex
             * @memberOf KJUR.asn1.ASN1Util
             * @function
             * @param {String} oidString dot noted string of object identifier
             * @return {String} hexadecimal value of object identifier
             * @since jsrsasign 4.8.3 asn1 1.0.7
             * @description
             * This static method converts from object identifier value string.
             * to hexadecimal string representation of it.
             * @example
             * KJUR.asn1.ASN1Util.oidIntToHex("2.5.4.6") &rarr; "550406"
             */
            KJUR.asn1.ASN1Util.oidIntToHex = function(oidString) {
                var itox = function(i) {
                    var h = i.toString(16);
                    if (h.length == 1) h = '0' + h;
                    return h;
                };

                var roidtox = function(roid) {
                    var h = '';
                    var bi = new BigInteger(roid, 10);
                    var b = bi.toString(2);
                    var padLen = 7 - b.length % 7;
                    if (padLen == 7) padLen = 0;
                    var bPad = '';
                    for (var i = 0; i < padLen; i++) bPad += '0';
                    b = bPad + b;
                    for (var i = 0; i < b.length - 1; i += 7) {
                        var b8 = b.substr(i, 7);
                        if (i != b.length - 7) b8 = '1' + b8;
                        h += itox(parseInt(b8, 2));
                    }
                    return h;
                };

                if (! oidString.match(/^[0-9.]+$/)) {
                    throw "malformed oid string: " + oidString;
                }
                var h = '';
                var a = oidString.split('.');
                var i0 = parseInt(a[0]) * 40 + parseInt(a[1]);
                h += itox(i0);
                a.splice(0, 2);
                for (var i = 0; i < a.length; i++) {
                    h += roidtox(a[i]);
                }
                return h;
            };


            // ********************************************************************
            //  Abstract ASN.1 Classes
            // ********************************************************************

            // ********************************************************************

            /**
             * base class for ASN.1 DER encoder object
             * @name KJUR.asn1.ASN1Object
             * @class base class for ASN.1 DER encoder object
             * @property {Boolean} isModified flag whether internal data was changed
             * @property {String} hTLV hexadecimal string of ASN.1 TLV
             * @property {String} hT hexadecimal string of ASN.1 TLV tag(T)
             * @property {String} hL hexadecimal string of ASN.1 TLV length(L)
             * @property {String} hV hexadecimal string of ASN.1 TLV value(V)
             * @description
             */
            KJUR.asn1.ASN1Object = function() {
                var hV = '';

                /**
                 * get hexadecimal ASN.1 TLV length(L) bytes from TLV value(V)
                 * @name getLengthHexFromValue
                 * @memberOf KJUR.asn1.ASN1Object#
                 * @function
                 * @return {String} hexadecimal string of ASN.1 TLV length(L)
                 */
                this.getLengthHexFromValue = function() {
                    if (typeof this.hV == "undefined" || this.hV == null) {
                        throw "this.hV is null or undefined.";
                    }
                    if (this.hV.length % 2 == 1) {
                        throw "value hex must be even length: n=" + hV.length + ",v=" + this.hV;
                    }
                    var n = this.hV.length / 2;
                    var hN = n.toString(16);
                    if (hN.length % 2 == 1) {
                        hN = "0" + hN;
                    }
                    if (n < 128) {
                        return hN;
                    } else {
                        var hNlen = hN.length / 2;
                        if (hNlen > 15) {
                            throw "ASN.1 length too long to represent by 8x: n = " + n.toString(16);
                        }
                        var head = 128 + hNlen;
                        return head.toString(16) + hN;
                    }
                };

                /**
                 * get hexadecimal string of ASN.1 TLV bytes
                 * @name getEncodedHex
                 * @memberOf KJUR.asn1.ASN1Object#
                 * @function
                 * @return {String} hexadecimal string of ASN.1 TLV
                 */
                this.getEncodedHex = function() {
                    if (this.hTLV == null || this.isModified) {
                        this.hV = this.getFreshValueHex();
                        this.hL = this.getLengthHexFromValue();
                        this.hTLV = this.hT + this.hL + this.hV;
                        this.isModified = false;
                        //alert("first time: " + this.hTLV);
                    }
                    return this.hTLV;
                };

                /**
                 * get hexadecimal string of ASN.1 TLV value(V) bytes
                 * @name getValueHex
                 * @memberOf KJUR.asn1.ASN1Object#
                 * @function
                 * @return {String} hexadecimal string of ASN.1 TLV value(V) bytes
                 */
                this.getValueHex = function() {
                    this.getEncodedHex();
                    return this.hV;
                };

                this.getFreshValueHex = function() {
                    return '';
                };
            };

            // == BEGIN DERAbstractString ================================================
            /**
             * base class for ASN.1 DER string classes
             * @name KJUR.asn1.DERAbstractString
             * @class base class for ASN.1 DER string classes
             * @param {Array} params associative array of parameters (ex. {'str': 'aaa'})
             * @property {String} s internal string of value
             * @extends KJUR.asn1.ASN1Object
             * @description
             * <br/>
             * As for argument 'params' for constructor, you can specify one of
             * following properties:
             * <ul>
             * <li>str - specify initial ASN.1 value(V) by a string</li>
             * <li>hex - specify initial ASN.1 value(V) by a hexadecimal string</li>
             * </ul>
             * NOTE: 'params' can be omitted.
             */
            KJUR.asn1.DERAbstractString = function(params) {
                KJUR.asn1.DERAbstractString.superclass.constructor.call(this);

                /**
                 * get string value of this string object
                 * @name getString
                 * @memberOf KJUR.asn1.DERAbstractString#
                 * @function
                 * @return {String} string value of this string object
                 */
                this.getString = function() {
                    return this.s;
                };

                /**
                 * set value by a string
                 * @name setString
                 * @memberOf KJUR.asn1.DERAbstractString#
                 * @function
                 * @param {String} newS value by a string to set
                 */
                this.setString = function(newS) {
                    this.hTLV = null;
                    this.isModified = true;
                    this.s = newS;
                    this.hV = stohex(this.s);
                };

                /**
                 * set value by a hexadecimal string
                 * @name setStringHex
                 * @memberOf KJUR.asn1.DERAbstractString#
                 * @function
                 * @param {String} newHexString value by a hexadecimal string to set
                 */
                this.setStringHex = function(newHexString) {
                    this.hTLV = null;
                    this.isModified = true;
                    this.s = null;
                    this.hV = newHexString;
                };

                this.getFreshValueHex = function() {
                    return this.hV;
                };

                if (typeof params != "undefined") {
                    if (typeof params == "string") {
                        this.setString(params);
                    } else if (typeof params['str'] != "undefined") {
                        this.setString(params['str']);
                    } else if (typeof params['hex'] != "undefined") {
                        this.setStringHex(params['hex']);
                    }
                }
            };
            YAHOO.lang.extend(KJUR.asn1.DERAbstractString, KJUR.asn1.ASN1Object);
            // == END   DERAbstractString ================================================

            // == BEGIN DERAbstractTime ==================================================
            /**
             * base class for ASN.1 DER Generalized/UTCTime class
             * @name KJUR.asn1.DERAbstractTime
             * @class base class for ASN.1 DER Generalized/UTCTime class
             * @param {Array} params associative array of parameters (ex. {'str': '130430235959Z'})
             * @extends KJUR.asn1.ASN1Object
             * @description
             * @see KJUR.asn1.ASN1Object - superclass
             */
            KJUR.asn1.DERAbstractTime = function(params) {
                KJUR.asn1.DERAbstractTime.superclass.constructor.call(this);

                // --- PRIVATE METHODS --------------------
                this.localDateToUTC = function(d) {
                    utc = d.getTime() + (d.getTimezoneOffset() * 60000);
                    var utcDate = new Date(utc);
                    return utcDate;
                };

                /*
         * format date string by Data object
         * @name formatDate
         * @memberOf KJUR.asn1.AbstractTime;
         * @param {Date} dateObject
         * @param {string} type 'utc' or 'gen'
         * @param {boolean} withMillis flag for with millisections or not
         * @description
         * 'withMillis' flag is supported from asn1 1.0.6.
         */
                this.formatDate = function(dateObject, type, withMillis) {
                    var pad = this.zeroPadding;
                    var d = this.localDateToUTC(dateObject);
                    var year = String(d.getFullYear());
                    if (type == 'utc') year = year.substr(2, 2);
                    var month = pad(String(d.getMonth() + 1), 2);
                    var day = pad(String(d.getDate()), 2);
                    var hour = pad(String(d.getHours()), 2);
                    var min = pad(String(d.getMinutes()), 2);
                    var sec = pad(String(d.getSeconds()), 2);
                    var s = year + month + day + hour + min + sec;
                    if (withMillis === true) {
                        var millis = d.getMilliseconds();
                        if (millis != 0) {
                            var sMillis = pad(String(millis), 3);
                            sMillis = sMillis.replace(/[0]+$/, "");
                            s = s + "." + sMillis;
                        }
                    }
                    return s + "Z";
                };

                this.zeroPadding = function(s, len) {
                    if (s.length >= len) return s;
                    return new Array(len - s.length + 1).join('0') + s;
                };

                // --- PUBLIC METHODS --------------------
                /**
                 * get string value of this string object
                 * @name getString
                 * @memberOf KJUR.asn1.DERAbstractTime#
                 * @function
                 * @return {String} string value of this time object
                 */
                this.getString = function() {
                    return this.s;
                };

                /**
                 * set value by a string
                 * @name setString
                 * @memberOf KJUR.asn1.DERAbstractTime#
                 * @function
                 * @param {String} newS value by a string to set such like "130430235959Z"
                 */
                this.setString = function(newS) {
                    this.hTLV = null;
                    this.isModified = true;
                    this.s = newS;
                    this.hV = stohex(newS);
                };

                /**
                 * set value by a Date object
                 * @name setByDateValue
                 * @memberOf KJUR.asn1.DERAbstractTime#
                 * @function
                 * @param {Integer} year year of date (ex. 2013)
                 * @param {Integer} month month of date between 1 and 12 (ex. 12)
                 * @param {Integer} day day of month
                 * @param {Integer} hour hours of date
                 * @param {Integer} min minutes of date
                 * @param {Integer} sec seconds of date
                 */
                this.setByDateValue = function(year, month, day, hour, min, sec) {
                    var dateObject = new Date(Date.UTC(year, month - 1, day, hour, min, sec, 0));
                    this.setByDate(dateObject);
                };

                this.getFreshValueHex = function() {
                    return this.hV;
                };
            };
            YAHOO.lang.extend(KJUR.asn1.DERAbstractTime, KJUR.asn1.ASN1Object);
            // == END   DERAbstractTime ==================================================

            // == BEGIN DERAbstractStructured ============================================
            /**
             * base class for ASN.1 DER structured class
             * @name KJUR.asn1.DERAbstractStructured
             * @class base class for ASN.1 DER structured class
             * @property {Array} asn1Array internal array of ASN1Object
             * @extends KJUR.asn1.ASN1Object
             * @description
             * @see KJUR.asn1.ASN1Object - superclass
             */
            KJUR.asn1.DERAbstractStructured = function(params) {
                KJUR.asn1.DERAbstractString.superclass.constructor.call(this);

                /**
                 * set value by array of ASN1Object
                 * @name setByASN1ObjectArray
                 * @memberOf KJUR.asn1.DERAbstractStructured#
                 * @function
                 * @param {array} asn1ObjectArray array of ASN1Object to set
                 */
                this.setByASN1ObjectArray = function(asn1ObjectArray) {
                    this.hTLV = null;
                    this.isModified = true;
                    this.asn1Array = asn1ObjectArray;
                };

                /**
                 * append an ASN1Object to internal array
                 * @name appendASN1Object
                 * @memberOf KJUR.asn1.DERAbstractStructured#
                 * @function
                 * @param {ASN1Object} asn1Object to add
                 */
                this.appendASN1Object = function(asn1Object) {
                    this.hTLV = null;
                    this.isModified = true;
                    this.asn1Array.push(asn1Object);
                };

                this.asn1Array = new Array();
                if (typeof params != "undefined") {
                    if (typeof params['array'] != "undefined") {
                        this.asn1Array = params['array'];
                    }
                }
            };
            YAHOO.lang.extend(KJUR.asn1.DERAbstractStructured, KJUR.asn1.ASN1Object);


            // ********************************************************************
            //  ASN.1 Object Classes
            // ********************************************************************

            // ********************************************************************
            /**
             * class for ASN.1 DER Boolean
             * @name KJUR.asn1.DERBoolean
             * @class class for ASN.1 DER Boolean
             * @extends KJUR.asn1.ASN1Object
             * @description
             * @see KJUR.asn1.ASN1Object - superclass
             */
            KJUR.asn1.DERBoolean = function() {
                KJUR.asn1.DERBoolean.superclass.constructor.call(this);
                this.hT = "01";
                this.hTLV = "0101ff";
            };
            YAHOO.lang.extend(KJUR.asn1.DERBoolean, KJUR.asn1.ASN1Object);

            // ********************************************************************
            /**
             * class for ASN.1 DER Integer
             * @name KJUR.asn1.DERInteger
             * @class class for ASN.1 DER Integer
             * @extends KJUR.asn1.ASN1Object
             * @description
             * <br/>
             * As for argument 'params' for constructor, you can specify one of
             * following properties:
             * <ul>
             * <li>int - specify initial ASN.1 value(V) by integer value</li>
             * <li>bigint - specify initial ASN.1 value(V) by BigInteger object</li>
             * <li>hex - specify initial ASN.1 value(V) by a hexadecimal string</li>
             * </ul>
             * NOTE: 'params' can be omitted.
             */
            KJUR.asn1.DERInteger = function(params) {
                KJUR.asn1.DERInteger.superclass.constructor.call(this);
                this.hT = "02";

                /**
                 * set value by Tom Wu's BigInteger object
                 * @name setByBigInteger
                 * @memberOf KJUR.asn1.DERInteger#
                 * @function
                 * @param {BigInteger} bigIntegerValue to set
                 */
                this.setByBigInteger = function(bigIntegerValue) {
                    this.hTLV = null;
                    this.isModified = true;
                    this.hV = KJUR.asn1.ASN1Util.bigIntToMinTwosComplementsHex(bigIntegerValue);
                };

                /**
                 * set value by integer value
                 * @name setByInteger
                 * @memberOf KJUR.asn1.DERInteger
                 * @function
                 * @param {Integer} integer value to set
                 */
                this.setByInteger = function(intValue) {
                    var bi = new BigInteger(String(intValue), 10);
                    this.setByBigInteger(bi);
                };

                /**
                 * set value by integer value
                 * @name setValueHex
                 * @memberOf KJUR.asn1.DERInteger#
                 * @function
                 * @param {String} hexadecimal string of integer value
                 * @description
                 * <br/>
                 * NOTE: Value shall be represented by minimum octet length of
                 * two's complement representation.
                 * @example
                 * new KJUR.asn1.DERInteger(123);
                 * new KJUR.asn1.DERInteger({'int': 123});
                 * new KJUR.asn1.DERInteger({'hex': '1fad'});
                 */
                this.setValueHex = function(newHexString) {
                    this.hV = newHexString;
                };

                this.getFreshValueHex = function() {
                    return this.hV;
                };

                if (typeof params != "undefined") {
                    if (typeof params['bigint'] != "undefined") {
                        this.setByBigInteger(params['bigint']);
                    } else if (typeof params['int'] != "undefined") {
                        this.setByInteger(params['int']);
                    } else if (typeof params == "number") {
                        this.setByInteger(params);
                    } else if (typeof params['hex'] != "undefined") {
                        this.setValueHex(params['hex']);
                    }
                }
            };
            YAHOO.lang.extend(KJUR.asn1.DERInteger, KJUR.asn1.ASN1Object);

            // ********************************************************************
            /**
             * class for ASN.1 DER encoded BitString primitive
             * @name KJUR.asn1.DERBitString
             * @class class for ASN.1 DER encoded BitString primitive
             * @extends KJUR.asn1.ASN1Object
             * @description
             * <br/>
             * As for argument 'params' for constructor, you can specify one of
             * following properties:
             * <ul>
             * <li>bin - specify binary string (ex. '10111')</li>
             * <li>array - specify array of boolean (ex. [true,false,true,true])</li>
             * <li>hex - specify hexadecimal string of ASN.1 value(V) including unused bits</li>
             * <li>obj - specify {@link KJUR.asn1.ASN1Util.newObject}
             * argument for "BitString encapsulates" structure.</li>
             * </ul>
             * NOTE1: 'params' can be omitted.<br/>
             * NOTE2: 'obj' parameter have been supported since
             * asn1 1.0.11, jsrsasign 6.1.1 (2016-Sep-25).<br/>
             * @example
             * // default constructor
             * o = new KJUR.asn1.DERBitString();
             * // initialize with binary string
             * o = new KJUR.asn1.DERBitString({bin: "1011"});
             * // initialize with boolean array
             * o = new KJUR.asn1.DERBitString({array: [true,false,true,true]});
             * // initialize with hexadecimal string (04 is unused bits)
             * o = new KJUR.asn1.DEROctetString({hex: "04bac0"});
             * // initialize with ASN1Util.newObject argument for encapsulated
             * o = new KJUR.asn1.DERBitString({obj: {seq: [{int: 3}, {prnstr: 'aaa'}]}});
             * // above generates a ASN.1 data like this:
             * // BIT STRING, encapsulates {
             * //   SEQUENCE {
             * //     INTEGER 3
             * //     PrintableString 'aaa'
             * //     }
             * //   }
             */
            KJUR.asn1.DERBitString = function(params) {
                if (params !== undefined && typeof params.obj !== "undefined") {
                    var o = KJUR.asn1.ASN1Util.newObject(params.obj);
                    params.hex = "00" + o.getEncodedHex();
                }
                KJUR.asn1.DERBitString.superclass.constructor.call(this);
                this.hT = "03";

                /**
                 * set ASN.1 value(V) by a hexadecimal string including unused bits
                 * @name setHexValueIncludingUnusedBits
                 * @memberOf KJUR.asn1.DERBitString#
                 * @function
                 * @param {String} newHexStringIncludingUnusedBits
                 */
                this.setHexValueIncludingUnusedBits = function(newHexStringIncludingUnusedBits) {
                    this.hTLV = null;
                    this.isModified = true;
                    this.hV = newHexStringIncludingUnusedBits;
                };

                /**
                 * set ASN.1 value(V) by unused bit and hexadecimal string of value
                 * @name setUnusedBitsAndHexValue
                 * @memberOf KJUR.asn1.DERBitString#
                 * @function
                 * @param {Integer} unusedBits
                 * @param {String} hValue
                 */
                this.setUnusedBitsAndHexValue = function(unusedBits, hValue) {
                    if (unusedBits < 0 || 7 < unusedBits) {
                        throw "unused bits shall be from 0 to 7: u = " + unusedBits;
                    }
                    var hUnusedBits = "0" + unusedBits;
                    this.hTLV = null;
                    this.isModified = true;
                    this.hV = hUnusedBits + hValue;
                };

                /**
                 * set ASN.1 DER BitString by binary string<br/>
                 * @name setByBinaryString
                 * @memberOf KJUR.asn1.DERBitString#
                 * @function
                 * @param {String} binaryString binary value string (i.e. '10111')
                 * @description
                 * Its unused bits will be calculated automatically by length of
                 * 'binaryValue'. <br/>
                 * NOTE: Trailing zeros '0' will be ignored.
                 * @example
                 * o = new KJUR.asn1.DERBitString();
                 * o.setByBooleanArray("01011");
                 */
                this.setByBinaryString = function(binaryString) {
                    binaryString = binaryString.replace(/0+$/, '');
                    var unusedBits = 8 - binaryString.length % 8;
                    if (unusedBits == 8) unusedBits = 0;
                    for (var i = 0; i <= unusedBits; i++) {
                        binaryString += '0';
                    }
                    var h = '';
                    for (var i = 0; i < binaryString.length - 1; i += 8) {
                        var b = binaryString.substr(i, 8);
                        var x = parseInt(b, 2).toString(16);
                        if (x.length == 1) x = '0' + x;
                        h += x;
                    }
                    this.hTLV = null;
                    this.isModified = true;
                    this.hV = '0' + unusedBits + h;
                };

                /**
                 * set ASN.1 TLV value(V) by an array of boolean<br/>
                 * @name setByBooleanArray
                 * @memberOf KJUR.asn1.DERBitString#
                 * @function
                 * @param {array} booleanArray array of boolean (ex. [true, false, true])
                 * @description
                 * NOTE: Trailing falses will be ignored in the ASN.1 DER Object.
                 * @example
                 * o = new KJUR.asn1.DERBitString();
                 * o.setByBooleanArray([false, true, false, true, true]);
                 */
                this.setByBooleanArray = function(booleanArray) {
                    var s = '';
                    for (var i = 0; i < booleanArray.length; i++) {
                        if (booleanArray[i] == true) {
                            s += '1';
                        } else {
                            s += '0';
                        }
                    }
                    this.setByBinaryString(s);
                };

                /**
                 * generate an array of falses with specified length<br/>
                 * @name newFalseArray
                 * @memberOf KJUR.asn1.DERBitString
                 * @function
                 * @param {Integer} nLength length of array to generate
                 * @return {array} array of boolean falses
                 * @description
                 * This static method may be useful to initialize boolean array.
                 * @example
                 * o = new KJUR.asn1.DERBitString();
                 * o.newFalseArray(3) &rarr; [false, false, false]
                 */
                this.newFalseArray = function(nLength) {
                    var a = new Array(nLength);
                    for (var i = 0; i < nLength; i++) {
                        a[i] = false;
                    }
                    return a;
                };

                this.getFreshValueHex = function() {
                    return this.hV;
                };

                if (typeof params != "undefined") {
                    if (typeof params == "string" && params.toLowerCase().match(/^[0-9a-f]+$/)) {
                        this.setHexValueIncludingUnusedBits(params);
                    } else if (typeof params['hex'] != "undefined") {
                        this.setHexValueIncludingUnusedBits(params['hex']);
                    } else if (typeof params['bin'] != "undefined") {
                        this.setByBinaryString(params['bin']);
                    } else if (typeof params['array'] != "undefined") {
                        this.setByBooleanArray(params['array']);
                    }
                }
            };
            YAHOO.lang.extend(KJUR.asn1.DERBitString, KJUR.asn1.ASN1Object);

            // ********************************************************************
            /**
             * class for ASN.1 DER OctetString<br/>
             * @name KJUR.asn1.DEROctetString
             * @class class for ASN.1 DER OctetString
             * @param {Array} params associative array of parameters (ex. {'str': 'aaa'})
             * @extends KJUR.asn1.DERAbstractString
             * @description
             * This class provides ASN.1 OctetString simple type.<br/>
             * Supported "params" attributes are:
             * <ul>
             * <li>str - to set a string as a value</li>
             * <li>hex - to set a hexadecimal string as a value</li>
             * <li>obj - to set a encapsulated ASN.1 value by JSON object
             * which is defined in {@link KJUR.asn1.ASN1Util.newObject}</li>
             * </ul>
             * NOTE: A parameter 'obj' have been supported
             * for "OCTET STRING, encapsulates" structure.
             * since asn1 1.0.11, jsrsasign 6.1.1 (2016-Sep-25).
             * @see KJUR.asn1.DERAbstractString - superclass
             * @example
             * // default constructor
             * o = new KJUR.asn1.DEROctetString();
             * // initialize with string
             * o = new KJUR.asn1.DEROctetString({str: "aaa"});
             * // initialize with hexadecimal string
             * o = new KJUR.asn1.DEROctetString({hex: "616161"});
             * // initialize with ASN1Util.newObject argument
             * o = new KJUR.asn1.DEROctetString({obj: {seq: [{int: 3}, {prnstr: 'aaa'}]}});
             * // above generates a ASN.1 data like this:
             * // OCTET STRING, encapsulates {
             * //   SEQUENCE {
             * //     INTEGER 3
             * //     PrintableString 'aaa'
             * //     }
             * //   }
             */
            KJUR.asn1.DEROctetString = function(params) {
                if (params !== undefined && typeof params.obj !== "undefined") {
                    var o = KJUR.asn1.ASN1Util.newObject(params.obj);
                    params.hex = o.getEncodedHex();
                }
                KJUR.asn1.DEROctetString.superclass.constructor.call(this, params);
                this.hT = "04";
            };
            YAHOO.lang.extend(KJUR.asn1.DEROctetString, KJUR.asn1.DERAbstractString);

            // ********************************************************************
            /**
             * class for ASN.1 DER Null
             * @name KJUR.asn1.DERNull
             * @class class for ASN.1 DER Null
             * @extends KJUR.asn1.ASN1Object
             * @description
             * @see KJUR.asn1.ASN1Object - superclass
             */
            KJUR.asn1.DERNull = function() {
                KJUR.asn1.DERNull.superclass.constructor.call(this);
                this.hT = "05";
                this.hTLV = "0500";
            };
            YAHOO.lang.extend(KJUR.asn1.DERNull, KJUR.asn1.ASN1Object);

            // ********************************************************************
            /**
             * class for ASN.1 DER ObjectIdentifier
             * @name KJUR.asn1.DERObjectIdentifier
             * @class class for ASN.1 DER ObjectIdentifier
             * @param {Array} params associative array of parameters (ex. {'oid': '2.5.4.5'})
             * @extends KJUR.asn1.ASN1Object
             * @description
             * <br/>
             * As for argument 'params' for constructor, you can specify one of
             * following properties:
             * <ul>
             * <li>oid - specify initial ASN.1 value(V) by a oid string (ex. 2.5.4.13)</li>
             * <li>hex - specify initial ASN.1 value(V) by a hexadecimal string</li>
             * </ul>
             * NOTE: 'params' can be omitted.
             */
            KJUR.asn1.DERObjectIdentifier = function(params) {
                var itox = function(i) {
                    var h = i.toString(16);
                    if (h.length == 1) h = '0' + h;
                    return h;
                };
                var roidtox = function(roid) {
                    var h = '';
                    var bi = new BigInteger(roid, 10);
                    var b = bi.toString(2);
                    var padLen = 7 - b.length % 7;
                    if (padLen == 7) padLen = 0;
                    var bPad = '';
                    for (var i = 0; i < padLen; i++) bPad += '0';
                    b = bPad + b;
                    for (var i = 0; i < b.length - 1; i += 7) {
                        var b8 = b.substr(i, 7);
                        if (i != b.length - 7) b8 = '1' + b8;
                        h += itox(parseInt(b8, 2));
                    }
                    return h;
                };

                KJUR.asn1.DERObjectIdentifier.superclass.constructor.call(this);
                this.hT = "06";

                /**
                 * set value by a hexadecimal string
                 * @name setValueHex
                 * @memberOf KJUR.asn1.DERObjectIdentifier#
                 * @function
                 * @param {String} newHexString hexadecimal value of OID bytes
                 */
                this.setValueHex = function(newHexString) {
                    this.hTLV = null;
                    this.isModified = true;
                    this.s = null;
                    this.hV = newHexString;
                };

                /**
                 * set value by a OID string<br/>
                 * @name setValueOidString
                 * @memberOf KJUR.asn1.DERObjectIdentifier#
                 * @function
                 * @param {String} oidString OID string (ex. 2.5.4.13)
                 * @example
                 * o = new KJUR.asn1.DERObjectIdentifier();
                 * o.setValueOidString("2.5.4.13");
                 */
                this.setValueOidString = function(oidString) {
                    if (! oidString.match(/^[0-9.]+$/)) {
                        throw "malformed oid string: " + oidString;
                    }
                    var h = '';
                    var a = oidString.split('.');
                    var i0 = parseInt(a[0]) * 40 + parseInt(a[1]);
                    h += itox(i0);
                    a.splice(0, 2);
                    for (var i = 0; i < a.length; i++) {
                        h += roidtox(a[i]);
                    }
                    this.hTLV = null;
                    this.isModified = true;
                    this.s = null;
                    this.hV = h;
                };

                /**
                 * set value by a OID name
                 * @name setValueName
                 * @memberOf KJUR.asn1.DERObjectIdentifier#
                 * @function
                 * @param {String} oidName OID name (ex. 'serverAuth')
                 * @since 1.0.1
                 * @description
                 * OID name shall be defined in 'KJUR.asn1.x509.OID.name2oidList'.
                 * Otherwise raise error.
                 * @example
                 * o = new KJUR.asn1.DERObjectIdentifier();
                 * o.setValueName("serverAuth");
                 */
                this.setValueName = function(oidName) {
                    var oid = KJUR.asn1.x509.OID.name2oid(oidName);
                    if (oid !== '') {
                        this.setValueOidString(oid);
                    } else {
                        throw "DERObjectIdentifier oidName undefined: " + oidName;
                    }
                };

                this.getFreshValueHex = function() {
                    return this.hV;
                };

                if (params !== undefined) {
                    if (typeof params === "string") {
                        if (params.match(/^[0-2].[0-9.]+$/)) {
                            this.setValueOidString(params);
                        } else {
                            this.setValueName(params);
                        }
                    } else if (params.oid !== undefined) {
                        this.setValueOidString(params.oid);
                    } else if (params.hex !== undefined) {
                        this.setValueHex(params.hex);
                    } else if (params.name !== undefined) {
                        this.setValueName(params.name);
                    }
                }
            };
            YAHOO.lang.extend(KJUR.asn1.DERObjectIdentifier, KJUR.asn1.ASN1Object);

            // ********************************************************************
            /**
             * class for ASN.1 DER Enumerated
             * @name KJUR.asn1.DEREnumerated
             * @class class for ASN.1 DER Enumerated
             * @extends KJUR.asn1.ASN1Object
             * @description
             * <br/>
             * As for argument 'params' for constructor, you can specify one of
             * following properties:
             * <ul>
             * <li>int - specify initial ASN.1 value(V) by integer value</li>
             * <li>hex - specify initial ASN.1 value(V) by a hexadecimal string</li>
             * </ul>
             * NOTE: 'params' can be omitted.
             * @example
             * new KJUR.asn1.DEREnumerated(123);
             * new KJUR.asn1.DEREnumerated({int: 123});
             * new KJUR.asn1.DEREnumerated({hex: '1fad'});
             */
            KJUR.asn1.DEREnumerated = function(params) {
                KJUR.asn1.DEREnumerated.superclass.constructor.call(this);
                this.hT = "0a";

                /**
                 * set value by Tom Wu's BigInteger object
                 * @name setByBigInteger
                 * @memberOf KJUR.asn1.DEREnumerated#
                 * @function
                 * @param {BigInteger} bigIntegerValue to set
                 */
                this.setByBigInteger = function(bigIntegerValue) {
                    this.hTLV = null;
                    this.isModified = true;
                    this.hV = KJUR.asn1.ASN1Util.bigIntToMinTwosComplementsHex(bigIntegerValue);
                };

                /**
                 * set value by integer value
                 * @name setByInteger
                 * @memberOf KJUR.asn1.DEREnumerated#
                 * @function
                 * @param {Integer} integer value to set
                 */
                this.setByInteger = function(intValue) {
                    var bi = new BigInteger(String(intValue), 10);
                    this.setByBigInteger(bi);
                };

                /**
                 * set value by integer value
                 * @name setValueHex
                 * @memberOf KJUR.asn1.DEREnumerated#
                 * @function
                 * @param {String} hexadecimal string of integer value
                 * @description
                 * <br/>
                 * NOTE: Value shall be represented by minimum octet length of
                 * two's complement representation.
                 */
                this.setValueHex = function(newHexString) {
                    this.hV = newHexString;
                };

                this.getFreshValueHex = function() {
                    return this.hV;
                };

                if (typeof params != "undefined") {
                    if (typeof params['int'] != "undefined") {
                        this.setByInteger(params['int']);
                    } else if (typeof params == "number") {
                        this.setByInteger(params);
                    } else if (typeof params['hex'] != "undefined") {
                        this.setValueHex(params['hex']);
                    }
                }
            };
            YAHOO.lang.extend(KJUR.asn1.DEREnumerated, KJUR.asn1.ASN1Object);

            // ********************************************************************
            /**
             * class for ASN.1 DER UTF8String
             * @name KJUR.asn1.DERUTF8String
             * @class class for ASN.1 DER UTF8String
             * @param {Array} params associative array of parameters (ex. {'str': 'aaa'})
             * @extends KJUR.asn1.DERAbstractString
             * @description
             * @see KJUR.asn1.DERAbstractString - superclass
             */
            KJUR.asn1.DERUTF8String = function(params) {
                KJUR.asn1.DERUTF8String.superclass.constructor.call(this, params);
                this.hT = "0c";
            };
            YAHOO.lang.extend(KJUR.asn1.DERUTF8String, KJUR.asn1.DERAbstractString);

            // ********************************************************************
            /**
             * class for ASN.1 DER NumericString
             * @name KJUR.asn1.DERNumericString
             * @class class for ASN.1 DER NumericString
             * @param {Array} params associative array of parameters (ex. {'str': 'aaa'})
             * @extends KJUR.asn1.DERAbstractString
             * @description
             * @see KJUR.asn1.DERAbstractString - superclass
             */
            KJUR.asn1.DERNumericString = function(params) {
                KJUR.asn1.DERNumericString.superclass.constructor.call(this, params);
                this.hT = "12";
            };
            YAHOO.lang.extend(KJUR.asn1.DERNumericString, KJUR.asn1.DERAbstractString);

            // ********************************************************************
            /**
             * class for ASN.1 DER PrintableString
             * @name KJUR.asn1.DERPrintableString
             * @class class for ASN.1 DER PrintableString
             * @param {Array} params associative array of parameters (ex. {'str': 'aaa'})
             * @extends KJUR.asn1.DERAbstractString
             * @description
             * @see KJUR.asn1.DERAbstractString - superclass
             */
            KJUR.asn1.DERPrintableString = function(params) {
                KJUR.asn1.DERPrintableString.superclass.constructor.call(this, params);
                this.hT = "13";
            };
            YAHOO.lang.extend(KJUR.asn1.DERPrintableString, KJUR.asn1.DERAbstractString);

            // ********************************************************************
            /**
             * class for ASN.1 DER TeletexString
             * @name KJUR.asn1.DERTeletexString
             * @class class for ASN.1 DER TeletexString
             * @param {Array} params associative array of parameters (ex. {'str': 'aaa'})
             * @extends KJUR.asn1.DERAbstractString
             * @description
             * @see KJUR.asn1.DERAbstractString - superclass
             */
            KJUR.asn1.DERTeletexString = function(params) {
                KJUR.asn1.DERTeletexString.superclass.constructor.call(this, params);
                this.hT = "14";
            };
            YAHOO.lang.extend(KJUR.asn1.DERTeletexString, KJUR.asn1.DERAbstractString);

            // ********************************************************************
            /**
             * class for ASN.1 DER IA5String
             * @name KJUR.asn1.DERIA5String
             * @class class for ASN.1 DER IA5String
             * @param {Array} params associative array of parameters (ex. {'str': 'aaa'})
             * @extends KJUR.asn1.DERAbstractString
             * @description
             * @see KJUR.asn1.DERAbstractString - superclass
             */
            KJUR.asn1.DERIA5String = function(params) {
                KJUR.asn1.DERIA5String.superclass.constructor.call(this, params);
                this.hT = "16";
            };
            YAHOO.lang.extend(KJUR.asn1.DERIA5String, KJUR.asn1.DERAbstractString);

            // ********************************************************************
            /**
             * class for ASN.1 DER UTCTime
             * @name KJUR.asn1.DERUTCTime
             * @class class for ASN.1 DER UTCTime
             * @param {Array} params associative array of parameters (ex. {'str': '130430235959Z'})
             * @extends KJUR.asn1.DERAbstractTime
             * @description
             * <br/>
             * As for argument 'params' for constructor, you can specify one of
             * following properties:
             * <ul>
             * <li>str - specify initial ASN.1 value(V) by a string (ex.'130430235959Z')</li>
             * <li>hex - specify initial ASN.1 value(V) by a hexadecimal string</li>
             * <li>date - specify Date object.</li>
             * </ul>
             * NOTE: 'params' can be omitted.
             * <h4>EXAMPLES</h4>
             * @example
             * d1 = new KJUR.asn1.DERUTCTime();
             * d1.setString('130430125959Z');
             *
             * d2 = new KJUR.asn1.DERUTCTime({'str': '130430125959Z'});
             * d3 = new KJUR.asn1.DERUTCTime({'date': new Date(Date.UTC(2015, 0, 31, 0, 0, 0, 0))});
             * d4 = new KJUR.asn1.DERUTCTime('130430125959Z');
             */
            KJUR.asn1.DERUTCTime = function(params) {
                KJUR.asn1.DERUTCTime.superclass.constructor.call(this, params);
                this.hT = "17";

                /**
                 * set value by a Date object<br/>
                 * @name setByDate
                 * @memberOf KJUR.asn1.DERUTCTime#
                 * @function
                 * @param {Date} dateObject Date object to set ASN.1 value(V)
                 * @example
                 * o = new KJUR.asn1.DERUTCTime();
                 * o.setByDate(new Date("2016/12/31"));
                 */
                this.setByDate = function(dateObject) {
                    this.hTLV = null;
                    this.isModified = true;
                    this.date = dateObject;
                    this.s = this.formatDate(this.date, 'utc');
                    this.hV = stohex(this.s);
                };

                this.getFreshValueHex = function() {
                    if (typeof this.date == "undefined" && typeof this.s == "undefined") {
                        this.date = new Date();
                        this.s = this.formatDate(this.date, 'utc');
                        this.hV = stohex(this.s);
                    }
                    return this.hV;
                };

                if (params !== undefined) {
                    if (params.str !== undefined) {
                        this.setString(params.str);
                    } else if (typeof params == "string" && params.match(/^[0-9]{12}Z$/)) {
                        this.setString(params);
                    } else if (params.hex !== undefined) {
                        this.setStringHex(params.hex);
                    } else if (params.date !== undefined) {
                        this.setByDate(params.date);
                    }
                }
            };
            YAHOO.lang.extend(KJUR.asn1.DERUTCTime, KJUR.asn1.DERAbstractTime);

            // ********************************************************************
            /**
             * class for ASN.1 DER GeneralizedTime
             * @name KJUR.asn1.DERGeneralizedTime
             * @class class for ASN.1 DER GeneralizedTime
             * @param {Array} params associative array of parameters (ex. {'str': '20130430235959Z'})
             * @property {Boolean} withMillis flag to show milliseconds or not
             * @extends KJUR.asn1.DERAbstractTime
             * @description
             * <br/>
             * As for argument 'params' for constructor, you can specify one of
             * following properties:
             * <ul>
             * <li>str - specify initial ASN.1 value(V) by a string (ex.'20130430235959Z')</li>
             * <li>hex - specify initial ASN.1 value(V) by a hexadecimal string</li>
             * <li>date - specify Date object.</li>
             * <li>millis - specify flag to show milliseconds (from 1.0.6)</li>
             * </ul>
             * NOTE1: 'params' can be omitted.
             * NOTE2: 'withMillis' property is supported from asn1 1.0.6.
             */
            KJUR.asn1.DERGeneralizedTime = function(params) {
                KJUR.asn1.DERGeneralizedTime.superclass.constructor.call(this, params);
                this.hT = "18";
                this.withMillis = false;

                /**
                 * set value by a Date object
                 * @name setByDate
                 * @memberOf KJUR.asn1.DERGeneralizedTime#
                 * @function
                 * @param {Date} dateObject Date object to set ASN.1 value(V)
                 * @example
                 * When you specify UTC time, use 'Date.UTC' method like this:<br/>
                 * o1 = new DERUTCTime();
                 * o1.setByDate(date);
                 *
                 * date = new Date(Date.UTC(2015, 0, 31, 23, 59, 59, 0)); #2015JAN31 23:59:59
                 */
                this.setByDate = function(dateObject) {
                    this.hTLV = null;
                    this.isModified = true;
                    this.date = dateObject;
                    this.s = this.formatDate(this.date, 'gen', this.withMillis);
                    this.hV = stohex(this.s);
                };

                this.getFreshValueHex = function() {
                    if (this.date === undefined && this.s === undefined) {
                        this.date = new Date();
                        this.s = this.formatDate(this.date, 'gen', this.withMillis);
                        this.hV = stohex(this.s);
                    }
                    return this.hV;
                };

                if (params !== undefined) {
                    if (params.str !== undefined) {
                        this.setString(params.str);
                    } else if (typeof params == "string" && params.match(/^[0-9]{14}Z$/)) {
                        this.setString(params);
                    } else if (params.hex !== undefined) {
                        this.setStringHex(params.hex);
                    } else if (params.date !== undefined) {
                        this.setByDate(params.date);
                    }
                    if (params.millis === true) {
                        this.withMillis = true;
                    }
                }
            };
            YAHOO.lang.extend(KJUR.asn1.DERGeneralizedTime, KJUR.asn1.DERAbstractTime);

            // ********************************************************************
            /**
             * class for ASN.1 DER Sequence
             * @name KJUR.asn1.DERSequence
             * @class class for ASN.1 DER Sequence
             * @extends KJUR.asn1.DERAbstractStructured
             * @description
             * <br/>
             * As for argument 'params' for constructor, you can specify one of
             * following properties:
             * <ul>
             * <li>array - specify array of ASN1Object to set elements of content</li>
             * </ul>
             * NOTE: 'params' can be omitted.
             */
            KJUR.asn1.DERSequence = function(params) {
                KJUR.asn1.DERSequence.superclass.constructor.call(this, params);
                this.hT = "30";
                this.getFreshValueHex = function() {
                    var h = '';
                    for (var i = 0; i < this.asn1Array.length; i++) {
                        var asn1Obj = this.asn1Array[i];
                        h += asn1Obj.getEncodedHex();
                    }
                    this.hV = h;
                    return this.hV;
                };
            };
            YAHOO.lang.extend(KJUR.asn1.DERSequence, KJUR.asn1.DERAbstractStructured);

            // ********************************************************************
            /**
             * class for ASN.1 DER Set
             * @name KJUR.asn1.DERSet
             * @class class for ASN.1 DER Set
             * @extends KJUR.asn1.DERAbstractStructured
             * @description
             * <br/>
             * As for argument 'params' for constructor, you can specify one of
             * following properties:
             * <ul>
             * <li>array - specify array of ASN1Object to set elements of content</li>
             * <li>sortflag - flag for sort (default: true). ASN.1 BER is not sorted in 'SET OF'.</li>
             * </ul>
             * NOTE1: 'params' can be omitted.<br/>
             * NOTE2: sortflag is supported since 1.0.5.
             */
            KJUR.asn1.DERSet = function(params) {
                KJUR.asn1.DERSet.superclass.constructor.call(this, params);
                this.hT = "31";
                this.sortFlag = true; // item shall be sorted only in ASN.1 DER
                this.getFreshValueHex = function() {
                    var a = new Array();
                    for (var i = 0; i < this.asn1Array.length; i++) {
                        var asn1Obj = this.asn1Array[i];
                        a.push(asn1Obj.getEncodedHex());
                    }
                    if (this.sortFlag == true) a.sort();
                    this.hV = a.join('');
                    return this.hV;
                };

                if (typeof params != "undefined") {
                    if (typeof params.sortflag != "undefined" &&
                        params.sortflag == false)
                        this.sortFlag = false;
                }
            };
            YAHOO.lang.extend(KJUR.asn1.DERSet, KJUR.asn1.DERAbstractStructured);

            // ********************************************************************
            /**
             * class for ASN.1 DER TaggedObject
             * @name KJUR.asn1.DERTaggedObject
             * @class class for ASN.1 DER TaggedObject
             * @extends KJUR.asn1.ASN1Object
             * @description
             * <br/>
             * Parameter 'tagNoNex' is ASN.1 tag(T) value for this object.
             * For example, if you find '[1]' tag in a ASN.1 dump,
             * 'tagNoHex' will be 'a1'.
             * <br/>
             * As for optional argument 'params' for constructor, you can specify *ANY* of
             * following properties:
             * <ul>
             * <li>explicit - specify true if this is explicit tag otherwise false
             *     (default is 'true').</li>
             * <li>tag - specify tag (default is 'a0' which means [0])</li>
             * <li>obj - specify ASN1Object which is tagged</li>
             * </ul>
             * @example
             * d1 = new KJUR.asn1.DERUTF8String({'str':'a'});
             * d2 = new KJUR.asn1.DERTaggedObject({'obj': d1});
             * hex = d2.getEncodedHex();
             */
            KJUR.asn1.DERTaggedObject = function(params) {
                KJUR.asn1.DERTaggedObject.superclass.constructor.call(this);
                this.hT = "a0";
                this.hV = '';
                this.isExplicit = true;
                this.asn1Object = null;

                /**
                 * set value by an ASN1Object
                 * @name setString
                 * @memberOf KJUR.asn1.DERTaggedObject#
                 * @function
                 * @param {Boolean} isExplicitFlag flag for explicit/implicit tag
                 * @param {Integer} tagNoHex hexadecimal string of ASN.1 tag
                 * @param {ASN1Object} asn1Object ASN.1 to encapsulate
                 */
                this.setASN1Object = function(isExplicitFlag, tagNoHex, asn1Object) {
                    this.hT = tagNoHex;
                    this.isExplicit = isExplicitFlag;
                    this.asn1Object = asn1Object;
                    if (this.isExplicit) {
                        this.hV = this.asn1Object.getEncodedHex();
                        this.hTLV = null;
                        this.isModified = true;
                    } else {
                        this.hV = null;
                        this.hTLV = asn1Object.getEncodedHex();
                        this.hTLV = this.hTLV.replace(/^../, tagNoHex);
                        this.isModified = false;
                    }
                };

                this.getFreshValueHex = function() {
                    return this.hV;
                };

                if (typeof params != "undefined") {
                    if (typeof params['tag'] != "undefined") {
                        this.hT = params['tag'];
                    }
                    if (typeof params['explicit'] != "undefined") {
                        this.isExplicit = params['explicit'];
                    }
                    if (typeof params['obj'] != "undefined") {
                        this.asn1Object = params['obj'];
                        this.setASN1Object(this.isExplicit, this.hT, this.asn1Object);
                    }
                }
            };
            YAHOO.lang.extend(KJUR.asn1.DERTaggedObject, KJUR.asn1.ASN1Object);

            /**
             * Create a new JSEncryptRSAKey that extends Tom Wu's RSA key object.
             * This object is just a decorator for parsing the key parameter
             * @param {string|Object} key - The key in string format, or an object containing
             * the parameters needed to build a RSAKey object.
             * @constructor
             */
            var JSEncryptRSAKey = /** @class */ (function (_super) {
                __extends(JSEncryptRSAKey, _super);
                function JSEncryptRSAKey(key) {
                    var _this = _super.call(this) || this;
                    // Call the super constructor.
                    //  RSAKey.call(this);
                    // If a key key was provided.
                    if (key) {
                        // If this is a string...
                        if (typeof key === "string") {
                            _this.parseKey(key);
                        }
                        else if (JSEncryptRSAKey.hasPrivateKeyProperty(key) ||
                            JSEncryptRSAKey.hasPublicKeyProperty(key)) {
                            // Set the values for the key.
                            _this.parsePropertiesFrom(key);
                        }
                    }
                    return _this;
                }
                /**
                 * Method to parse a pem encoded string containing both a public or private key.
                 * The method will translate the pem encoded string in a der encoded string and
                 * will parse private key and public key parameters. This method accepts public key
                 * in the rsaencryption pkcs #1 format (oid: 1.2.840.113549.1.1.1).
                 *
                 * @todo Check how many rsa formats use the same format of pkcs #1.
                 *
                 * The format is defined as:
                 * PublicKeyInfo ::= SEQUENCE {
                 *   algorithm       AlgorithmIdentifier,
                 *   PublicKey       BIT STRING
                 * }
                 * Where AlgorithmIdentifier is:
                 * AlgorithmIdentifier ::= SEQUENCE {
                 *   algorithm       OBJECT IDENTIFIER,     the OID of the enc algorithm
                 *   parameters      ANY DEFINED BY algorithm OPTIONAL (NULL for PKCS #1)
                 * }
                 * and PublicKey is a SEQUENCE encapsulated in a BIT STRING
                 * RSAPublicKey ::= SEQUENCE {
                 *   modulus           INTEGER,  -- n
                 *   publicExponent    INTEGER   -- e
                 * }
                 * it's possible to examine the structure of the keys obtained from openssl using
                 * an asn.1 dumper as the one used here to parse the components: http://lapo.it/asn1js/
                 * @argument {string} pem the pem encoded string, can include the BEGIN/END header/footer
                 * @private
                 */
                JSEncryptRSAKey.prototype.parseKey = function (pem) {
                    try {
                        var modulus = 0;
                        var public_exponent = 0;
                        var reHex = /^\s*(?:[0-9A-Fa-f][0-9A-Fa-f]\s*)+$/;
                        var der = reHex.test(pem) ? Hex.decode(pem) : Base64.unarmor(pem);
                        var asn1 = ASN1.decode(der);
                        // Fixes a bug with OpenSSL 1.0+ private keys
                        if (asn1.sub.length === 3) {
                            asn1 = asn1.sub[2].sub[0];
                        }
                        if (asn1.sub.length === 9) {
                            // Parse the private key.
                            modulus = asn1.sub[1].getHexStringValue(); // bigint
                            this.n = parseBigInt(modulus, 16);
                            public_exponent = asn1.sub[2].getHexStringValue(); // int
                            this.e = parseInt(public_exponent, 16);
                            var private_exponent = asn1.sub[3].getHexStringValue(); // bigint
                            this.d = parseBigInt(private_exponent, 16);
                            var prime1 = asn1.sub[4].getHexStringValue(); // bigint
                            this.p = parseBigInt(prime1, 16);
                            var prime2 = asn1.sub[5].getHexStringValue(); // bigint
                            this.q = parseBigInt(prime2, 16);
                            var exponent1 = asn1.sub[6].getHexStringValue(); // bigint
                            this.dmp1 = parseBigInt(exponent1, 16);
                            var exponent2 = asn1.sub[7].getHexStringValue(); // bigint
                            this.dmq1 = parseBigInt(exponent2, 16);
                            var coefficient = asn1.sub[8].getHexStringValue(); // bigint
                            this.coeff = parseBigInt(coefficient, 16);
                        }
                        else if (asn1.sub.length === 2) {
                            // Parse the public key.
                            var bit_string = asn1.sub[1];
                            var sequence = bit_string.sub[0];
                            modulus = sequence.sub[0].getHexStringValue();
                            this.n = parseBigInt(modulus, 16);
                            public_exponent = sequence.sub[1].getHexStringValue();
                            this.e = parseInt(public_exponent, 16);
                        }
                        else {
                            return false;
                        }
                        return true;
                    }
                    catch (ex) {
                        return false;
                    }
                };
                /**
                 * Translate rsa parameters in a hex encoded string representing the rsa key.
                 *
                 * The translation follow the ASN.1 notation :
                 * RSAPrivateKey ::= SEQUENCE {
                 *   version           Version,
                 *   modulus           INTEGER,  -- n
                 *   publicExponent    INTEGER,  -- e
                 *   privateExponent   INTEGER,  -- d
                 *   prime1            INTEGER,  -- p
                 *   prime2            INTEGER,  -- q
                 *   exponent1         INTEGER,  -- d mod (p1)
                 *   exponent2         INTEGER,  -- d mod (q-1)
                 *   coefficient       INTEGER,  -- (inverse of q) mod p
                 * }
                 * @returns {string}  DER Encoded String representing the rsa private key
                 * @private
                 */
                JSEncryptRSAKey.prototype.getPrivateBaseKey = function () {
                    var options = {
                        array: [
                            new KJUR.asn1.DERInteger({ int: 0 }),
                            new KJUR.asn1.DERInteger({ bigint: this.n }),
                            new KJUR.asn1.DERInteger({ int: this.e }),
                            new KJUR.asn1.DERInteger({ bigint: this.d }),
                            new KJUR.asn1.DERInteger({ bigint: this.p }),
                            new KJUR.asn1.DERInteger({ bigint: this.q }),
                            new KJUR.asn1.DERInteger({ bigint: this.dmp1 }),
                            new KJUR.asn1.DERInteger({ bigint: this.dmq1 }),
                            new KJUR.asn1.DERInteger({ bigint: this.coeff })
                        ]
                    };
                    var seq = new KJUR.asn1.DERSequence(options);
                    return seq.getEncodedHex();
                };
                /**
                 * base64 (pem) encoded version of the DER encoded representation
                 * @returns {string} pem encoded representation without header and footer
                 * @public
                 */
                JSEncryptRSAKey.prototype.getPrivateBaseKeyB64 = function () {
                    return hex2b64(this.getPrivateBaseKey());
                };
                /**
                 * Translate rsa parameters in a hex encoded string representing the rsa public key.
                 * The representation follow the ASN.1 notation :
                 * PublicKeyInfo ::= SEQUENCE {
                 *   algorithm       AlgorithmIdentifier,
                 *   PublicKey       BIT STRING
                 * }
                 * Where AlgorithmIdentifier is:
                 * AlgorithmIdentifier ::= SEQUENCE {
                 *   algorithm       OBJECT IDENTIFIER,     the OID of the enc algorithm
                 *   parameters      ANY DEFINED BY algorithm OPTIONAL (NULL for PKCS #1)
                 * }
                 * and PublicKey is a SEQUENCE encapsulated in a BIT STRING
                 * RSAPublicKey ::= SEQUENCE {
                 *   modulus           INTEGER,  -- n
                 *   publicExponent    INTEGER   -- e
                 * }
                 * @returns {string} DER Encoded String representing the rsa public key
                 * @private
                 */
                JSEncryptRSAKey.prototype.getPublicBaseKey = function () {
                    var first_sequence = new KJUR.asn1.DERSequence({
                        array: [
                            new KJUR.asn1.DERObjectIdentifier({ oid: "1.2.840.113549.1.1.1" }),
                            new KJUR.asn1.DERNull()
                        ]
                    });
                    var second_sequence = new KJUR.asn1.DERSequence({
                        array: [
                            new KJUR.asn1.DERInteger({ bigint: this.n }),
                            new KJUR.asn1.DERInteger({ int: this.e })
                        ]
                    });
                    var bit_string = new KJUR.asn1.DERBitString({
                        hex: "00" + second_sequence.getEncodedHex()
                    });
                    var seq = new KJUR.asn1.DERSequence({
                        array: [
                            first_sequence,
                            bit_string
                        ]
                    });
                    return seq.getEncodedHex();
                };
                /**
                 * base64 (pem) encoded version of the DER encoded representation
                 * @returns {string} pem encoded representation without header and footer
                 * @public
                 */
                JSEncryptRSAKey.prototype.getPublicBaseKeyB64 = function () {
                    return hex2b64(this.getPublicBaseKey());
                };
                /**
                 * wrap the string in block of width chars. The default value for rsa keys is 64
                 * characters.
                 * @param {string} str the pem encoded string without header and footer
                 * @param {Number} [width=64] - the length the string has to be wrapped at
                 * @returns {string}
                 * @private
                 */
                JSEncryptRSAKey.wordwrap = function (str, width) {
                    width = width || 64;
                    if (!str) {
                        return str;
                    }
                    var regex = "(.{1," + width + "})( +|$\n?)|(.{1," + width + "})";
                    return str.match(RegExp(regex, "g")).join("\n");
                };
                /**
                 * Retrieve the pem encoded private key
                 * @returns {string} the pem encoded private key with header/footer
                 * @public
                 */
                JSEncryptRSAKey.prototype.getPrivateKey = function () {
                    var key = "-----BEGIN RSA PRIVATE KEY-----\n";
                    key += JSEncryptRSAKey.wordwrap(this.getPrivateBaseKeyB64()) + "\n";
                    key += "-----END RSA PRIVATE KEY-----";
                    return key;
                };
                /**
                 * Retrieve the pem encoded public key
                 * @returns {string} the pem encoded public key with header/footer
                 * @public
                 */
                JSEncryptRSAKey.prototype.getPublicKey = function () {
                    var key = "-----BEGIN PUBLIC KEY-----\n";
                    key += JSEncryptRSAKey.wordwrap(this.getPublicBaseKeyB64()) + "\n";
                    key += "-----END PUBLIC KEY-----";
                    return key;
                };
                /**
                 * Check if the object contains the necessary parameters to populate the rsa modulus
                 * and public exponent parameters.
                 * @param {Object} [obj={}] - An object that may contain the two public key
                 * parameters
                 * @returns {boolean} true if the object contains both the modulus and the public exponent
                 * properties (n and e)
                 * @todo check for types of n and e. N should be a parseable bigInt object, E should
                 * be a parseable integer number
                 * @private
                 */
                JSEncryptRSAKey.hasPublicKeyProperty = function (obj) {
                    obj = obj || {};
                    return (obj.hasOwnProperty("n") &&
                        obj.hasOwnProperty("e"));
                };
                /**
                 * Check if the object contains ALL the parameters of an RSA key.
                 * @param {Object} [obj={}] - An object that may contain nine rsa key
                 * parameters
                 * @returns {boolean} true if the object contains all the parameters needed
                 * @todo check for types of the parameters all the parameters but the public exponent
                 * should be parseable bigint objects, the public exponent should be a parseable integer number
                 * @private
                 */
                JSEncryptRSAKey.hasPrivateKeyProperty = function (obj) {
                    obj = obj || {};
                    return (obj.hasOwnProperty("n") &&
                        obj.hasOwnProperty("e") &&
                        obj.hasOwnProperty("d") &&
                        obj.hasOwnProperty("p") &&
                        obj.hasOwnProperty("q") &&
                        obj.hasOwnProperty("dmp1") &&
                        obj.hasOwnProperty("dmq1") &&
                        obj.hasOwnProperty("coeff"));
                };
                /**
                 * Parse the properties of obj in the current rsa object. Obj should AT LEAST
                 * include the modulus and public exponent (n, e) parameters.
                 * @param {Object} obj - the object containing rsa parameters
                 * @private
                 */
                JSEncryptRSAKey.prototype.parsePropertiesFrom = function (obj) {
                    this.n = obj.n;
                    this.e = obj.e;
                    if (obj.hasOwnProperty("d")) {
                        this.d = obj.d;
                        this.p = obj.p;
                        this.q = obj.q;
                        this.dmp1 = obj.dmp1;
                        this.dmq1 = obj.dmq1;
                        this.coeff = obj.coeff;
                    }
                };
                return JSEncryptRSAKey;
            }(RSAKey));

            /**
             *
             * @param {Object} [options = {}] - An object to customize JSEncrypt behaviour
             * possible parameters are:
             * - default_key_size        {number}  default: 1024 the key size in bit
             * - default_public_exponent {string}  default: '010001' the hexadecimal representation of the public exponent
             * - log                     {boolean} default: false whether log warn/error or not
             * @constructor
             */
            var JSEncrypt = /** @class */ (function () {
                function JSEncrypt(options) {
                    options = options || {};
                    this.default_key_size = parseInt(options.default_key_size, 10) || 1024;
                    this.default_public_exponent = options.default_public_exponent || "010001"; // 65537 default openssl public exponent for rsa key type
                    this.log = options.log || false;
                    // The private and public key.
                    this.key = null;
                }
                /**
                 * Method to set the rsa key parameter (one method is enough to set both the public
                 * and the private key, since the private key contains the public key paramenters)
                 * Log a warning if logs are enabled
                 * @param {Object|string} key the pem encoded string or an object (with or without header/footer)
                 * @public
                 */
                JSEncrypt.prototype.setKey = function (key) {
                    if (this.log && this.key) {
                        console.warn("A key was already set, overriding existing.");
                    }
                    this.key = new JSEncryptRSAKey(key);
                };
                /**
                 * Proxy method for setKey, for api compatibility
                 * @see setKey
                 * @public
                 */
                JSEncrypt.prototype.setPrivateKey = function (privkey) {
                    // Create the key.
                    this.setKey(privkey);
                };
                /**
                 * Proxy method for setKey, for api compatibility
                 * @see setKey
                 * @public
                 */
                JSEncrypt.prototype.setPublicKey = function (pubkey) {
                    // Sets the public key.
                    this.setKey(pubkey);
                };
                /**
                 * Proxy method for RSAKey object's decrypt, decrypt the string using the private
                 * components of the rsa key object. Note that if the object was not set will be created
                 * on the fly (by the getKey method) using the parameters passed in the JSEncrypt constructor
                 * @param {string} str base64 encoded crypted string to decrypt
                 * @return {string} the decrypted string
                 * @public
                 */
                JSEncrypt.prototype.decrypt = function (str) {
                    // Return the decrypted string.
                    try {
                        return this.getKey().decrypt(b64tohex(str));
                    }
                    catch (ex) {
                        return false;
                    }
                };
                /**
                 * Proxy method for RSAKey object's encrypt, encrypt the string using the public
                 * components of the rsa key object. Note that if the object was not set will be created
                 * on the fly (by the getKey method) using the parameters passed in the JSEncrypt constructor
                 * @param {string} str the string to encrypt
                 * @return {string} the encrypted string encoded in base64
                 * @public
                 */
                JSEncrypt.prototype.encrypt = function (str) {
                    // Return the encrypted string.
                    try {
                        return hex2b64(this.getKey().encrypt(str));
                    }
                    catch (ex) {
                        return false;
                    }
                };
                /**
                 * Proxy method for RSAKey object's sign.
                 * @param {string} str the string to sign
                 * @param {function} digestMethod hash method
                 * @param {string} digestName the name of the hash algorithm
                 * @return {string} the signature encoded in base64
                 * @public
                 */
                JSEncrypt.prototype.sign = function (str, digestMethod, digestName) {
                    // return the RSA signature of 'str' in 'hex' format.
                    try {
                        return hex2b64(this.getKey().sign(str, digestMethod, digestName));
                    }
                    catch (ex) {
                        return false;
                    }
                };
                /**
                 * Proxy method for RSAKey object's verify.
                 * @param {string} str the string to verify
                 * @param {string} signature the signature encoded in base64 to compare the string to
                 * @param {function} digestMethod hash method
                 * @return {boolean} whether the data and signature match
                 * @public
                 */
                JSEncrypt.prototype.verify = function (str, signature, digestMethod) {
                    // Return the decrypted 'digest' of the signature.
                    try {
                        return this.getKey().verify(str, b64tohex(signature), digestMethod);
                    }
                    catch (ex) {
                        return false;
                    }
                };
                /**
                 * Getter for the current JSEncryptRSAKey object. If it doesn't exists a new object
                 * will be created and returned
                 * @param {callback} [cb] the callback to be called if we want the key to be generated
                 * in an async fashion
                 * @returns {JSEncryptRSAKey} the JSEncryptRSAKey object
                 * @public
                 */
                JSEncrypt.prototype.getKey = function (cb) {
                    // Only create new if it does not exist.
                    if (!this.key) {
                        // Get a new private key.
                        this.key = new JSEncryptRSAKey();
                        if (cb && {}.toString.call(cb) === "[object Function]") {
                            this.key.generateAsync(this.default_key_size, this.default_public_exponent, cb);
                            return;
                        }
                        // Generate the key.
                        this.key.generate(this.default_key_size, this.default_public_exponent);
                    }
                    return this.key;
                };
                /**
                 * Returns the pem encoded representation of the private key
                 * If the key doesn't exists a new key will be created
                 * @returns {string} pem encoded representation of the private key WITH header and footer
                 * @public
                 */
                JSEncrypt.prototype.getPrivateKey = function () {
                    // Return the private representation of this key.
                    return this.getKey().getPrivateKey();
                };
                /**
                 * Returns the pem encoded representation of the private key
                 * If the key doesn't exists a new key will be created
                 * @returns {string} pem encoded representation of the private key WITHOUT header and footer
                 * @public
                 */
                JSEncrypt.prototype.getPrivateKeyB64 = function () {
                    // Return the private representation of this key.
                    return this.getKey().getPrivateBaseKeyB64();
                };
                /**
                 * Returns the pem encoded representation of the public key
                 * If the key doesn't exists a new key will be created
                 * @returns {string} pem encoded representation of the public key WITH header and footer
                 * @public
                 */
                JSEncrypt.prototype.getPublicKey = function () {
                    // Return the private representation of this key.
                    return this.getKey().getPublicKey();
                };
                /**
                 * Returns the pem encoded representation of the public key
                 * If the key doesn't exists a new key will be created
                 * @returns {string} pem encoded representation of the public key WITHOUT header and footer
                 * @public
                 */
                JSEncrypt.prototype.getPublicKeyB64 = function () {
                    // Return the private representation of this key.
                    return this.getKey().getPublicBaseKeyB64();
                };
                JSEncrypt.version = "3.0.0-rc.1";
                return JSEncrypt;
            }());

            window.JSEncrypt = JSEncrypt;

            exports.JSEncrypt = JSEncrypt;
            exports.default = JSEncrypt;

            Object.defineProperty(exports, '__esModule', { value: true });

        })));
    });

    var JSEncrypt = unwrapExports(jsencrypt);

    class OpenSSLEncrypter {
        /**
         * Encrypt the payload using the public key.
         *
         * @param publicKey
         * @param payload
         * @returns {string|null|PromiseLike<ArrayBuffer>}
         */
        encrypt(publicKey, payload) {
            let encrypter = new JSEncrypt();
            encrypter.setPublicKey(publicKey);
            return encrypter.encrypt(payload);
        }
    }

    class PaymentAuthorizationEvent extends GenericEvent {}

    class PaymentCanceledEvent extends GenericEvent {}

    class PaymentCompleteModal extends Modal {
        // eslint-disable-next-line
        render(parameters = {}) {
            return `${this.response.data.body}`;
        }
    }

    class PaymentCompleteResponse extends Response {
        /**
         * @param {Object} data
         */
        constructor(data) {
            super(data);
        }
    }

    /**
     * Expedites looking for a modal for given response.
     */
    class ResolveModalEvent extends GenericEvent
    {
        constructor(subject, parameters = {}) {
            super(subject, parameters);
            this.resolvedModal = null;
            this.resolvedParameters = {};
        }

        /**
         * @return {Modal}
         */
        getResolvedModal()
        {
            return this.resolvedModal;
        }

        /**
         * @param {Modal} modal
         */
        setResolvedModal(modal)
        {
            this.resolvedModal = modal;
        }
    }

    class RedirectionResponse extends ActionableResponse {
        /**
         * @param {Object} data
         */
        constructor(data) {
            super(data);
        }

        /**
         * @returns {String}
         */
        getNextUrl() {
            if (typeof this.data['nextUrl'] === 'undefined') {
                return null;
            }

            return this.data['nextUrl'];
        }
    }

    class TdsMethodResponse extends ActionableResponse {
        /**
         * @param {Object} data
         */
        constructor(data) {
            super(data);
        }

        getTdsData()
        {
            if (typeof this.data['threeDSMethodData'] === 'undefined') {
                return null;
            }

            return this.data['threeDSMethodData'];
        }

        getTdsUrl()
        {
            if (typeof this.data['threeDSMethodURL'] === 'undefined') {
                return null;
            }

            return this.data['threeDSMethodURL'];
        }
    }

    /**
     * @typedef BasicTransactionResponse
     * @property {Number} status
     * @property {String} type
     */
    class ResponseFactory {
        /**
         * Get response object
         *
         * @param {BasicTransactionResponse} data
         */
        createResponse(data) {
            if (typeof data['errorMessage'] != 'undefined') {
                return new ErrorResponse(data);
            }

            if (typeof data['nextAction'] != 'undefined') {
                switch (data['nextAction']) {
                    case 'error':
                    case 'submitToMpi':
                    case 'retryChallenge':
                    case 'promptForMpi':
                        return new ErrorResponse(data);
                    case 'redirectToShop':
                    case 'verify':
                    case 'paid':
                        return new RedirectionResponse(data);
                    case 'challenged':
                        return new ChallengedResponse(data);
                    case 'tdsMethod':
                        return new TdsMethodResponse(data);
                }
            }

            throw new Error('Unable to determine type for received response: ' + data['nextAction']);
        }
    }

    class UrlHelper {
        constructor(baseUrl) {
            this.baseUrl = typeof baseUrl === 'undefined' ? window.location.href : baseUrl;
        }

        /**
         * @param {String} url
         */
        ensureAbsoluteUrl(url) {
            if (typeof url === 'undefined') {
                return null;
            }

            if (!(url.indexOf('http://') === 0)
                && !(url.indexOf('https://') === 0)
            ) {
                url = new URL(url, this.baseUrl).href;
            }

            return url;
        }
    }

    class PaymentListener extends EventListener {
        /**
         * @param {PaymentCompleteEvent} event
         */
        static onPaymentCompleteEvent(event)
        {

            let resolveEvent = new ResolveModalEvent(new PaymentCompleteResponse({'body': '<p>Your payment was successfully received!</p>'}));

            EventDispatcher.getInstance().dispatch(resolveEvent, Events.onResolveModalEvent);

            /////

            EventDispatcher.getInstance().dispatch(new StateChangeEvent(event, {
                'state': {isPaid: true },
            }), Events.onStateChangeEvent);


            let modalOpenEvent = new ModalOpenEvent(resolveEvent.getResolvedModal(), resolveEvent.getParameters());

            EventDispatcher.getInstance().dispatch(modalOpenEvent, Events.onModalOpenEvent);

            let url = event.getParameter('paymentCompleteUrl');
            let urlHelper = new UrlHelper();

            console.log('GESTOPT REDIRET NAAR: ' + url);


            return;
            event.setParameter('paymentCompleteUrl', urlHelper.ensureAbsoluteUrl(url));

            return PaymentListener.handleRedirection(event, PaymentListener.createReturnUrl(event));
        }

        /**
         * @param {PaymentRequiresChallengeEvent} event
         */
        static onPaymentRequiresChallengeEvent(event) {
            let resolveEvent = new ResolveModalEvent(event.subject);
            EventDispatcher.getInstance().dispatch(resolveEvent, Events.onResolveModalEvent);

            let modalOpenEvent = new ModalOpenEvent(
                resolveEvent.getResolvedModal(), resolveEvent.getParameters()
            );

            EventDispatcher.getInstance().dispatch(modalOpenEvent, Events.onModalOpenEvent);
        }

        /**
         * @param {PaymentRequiresTdsMethodEvent} event
         */
        static onPaymentRequiresTdsMethodEvent(event) {
            let element = State.getInstance().getElementFromReference(Events$1.tdsMethodPlaceholderId);
            let response = event.getSubject();

            let frameHtml = '<html><head></head><body><form method="POST" action="' + response.getTdsUrl() + '"><input name="threeDSMethodData" value="' + response.getTdsData() + '" /></form><script type="text/javascript">(function(){ document.querySelector("form").submit() })();</script></body></html>';
            let frame = '<iframe id="tds-method-iframe" src="data:text/html;charset=utf-8,' + encodeURI(frameHtml) + '"></iframe>';

            let parser = new DOMParser();
            let domElement = parser.parseFromString(frame, 'text/html');

            element.innerHTML = '';
            element.appendChild(domElement.body.firstChild);
        }

        /**
         * Only called right now when coming from a polling response that indicates we are ready to re-authenticate.
         *
         * @param {PollingResponseEvent} event
         */
        static onPaymentAuthenticateEvent(event)
        {
            console.log('onPaymentAuthenticateEvent');
            EventDispatcher.getInstance().dispatch(new StateChangeEvent(event, {
                'state': {isAuthenticatingTds: true, loading:true},
            }), Events.onStateChangeEvent);

            let state = State.getInstance();
            let currentState = state.getCurrentState();
            let encryptedForm = event.getParameter('encryptedForm');
            let config = state.getConfig();
            let poller = event.getParameter('poller');
            let formData = new FormData();

            formData.set('pay_encrypted_data', currentState.payload);
            formData.set('transaction_id', currentState.transactionId);
            formData.set('entrance_code', currentState.entranceCode);
            formData.set('threeds_transaction_id', currentState.threeDSTransactionId);
            formData.set('acquirer_id', currentState.acquirerId);

            // stop poller, cause, why poll now ?
            poller.clear();
            console.log('poller stop');

            fetch(config.authentication_url, {
                'method': 'POST',
                'cache': 'no-cache',
                'redirect': 'follow',
                'body': formData
            }).then((response) => {
                if (response.status !== 200) {
                    throw Error('Unexpected status code returned');
                }

                return response.json().catch(() => {
                    throw new Error('Invalid JSON returned.');
                });
            }).then((json) =>
            {
                let responseFactory = new ResponseFactory();
                let response = responseFactory.createResponse(json);

                if (!response) {
                    throw new Error('Invalid response');
                }

                return response;
            }).then((response) => {
                if (response instanceof ActionableResponse) {
                    if (response instanceof RedirectionResponse) {
                        poller.clear();
                    }

                    EventDispatcher.getInstance().dispatch(new ActionableResponseEvent(response, {
                        'encryptedForm': encryptedForm,
                        'poller': poller,
                        'config': config
                    }), Events.onActionableResponseEvent);
                } else {
                    // @todo 3dsv2 catch errors and handle accordingly
                    poller.clear();
                }
            }).catch((exception) =>
            {
                console.log(exception);

                poller.clear();
            });

            EventDispatcher.getInstance().dispatch(new StateChangeEvent(event, {
                'state': {isAuthenticatingTds: false},
            }), Events.onStateChangeEvent);
        }

        /**
         * @param {PaymentAuthorizationEvent} event
         */

        static onPaymentAuthorizeEvent(event)
        {

            console.log('onPaymentAuthorizeEvent');

            let state = State.getInstance();
            let pollingEvent = event.getParameter('pollingEvent');

            let encryptedForm = pollingEvent.getParameter('encryptedForm');
            let config = pollingEvent.getParameter('config');
            let poller = pollingEvent.getParameter('poller');

            let formData = new FormData();

            formData.set('pay_encrypted_data', state.getCurrentState().payload);
            formData.set('transaction_id', state.getCurrentState().transactionId);
            formData.set('entrance_code', state.getCurrentState().entranceCode);
            formData.set('threeds_transaction_id', state.getCurrentState().threeDSTransactionId);
            formData.set('acquirer_id', state.getCurrentState().acquirerId);

            EventDispatcher.getInstance().dispatch(new ModalCloseEvent(null), Events.onModalCloseEvent);
            EventDispatcher.getInstance().dispatch(new StateChangeEvent(null, {
                'state': {loading: true, formSubmitted: true},
            }), Events.onStateChangeEvent);

            fetch(config.authorization_url, {
                'method': 'POST',
                'cache': 'no-cache',
                'redirect': 'follow',
                'body': formData
            }).then((response) => {
                if (response.status !== 200) {
                    throw Error('Unexpected status code returned');
                }

                return response.json().catch(() => {
                    throw new Error('Invalid JSON returned.');
                });
            }).then((json) => {
                let responseFactory = new ResponseFactory();
                let response = responseFactory.createResponse(json);

                if (!response) {
                    throw new Error('Invalid response');
                }

                return response;
            }).then((response) => {
                if (response instanceof ActionableResponse) {
                    EventDispatcher.getInstance().dispatch(new ActionableResponseEvent(response, {
                        'encryptedForm': encryptedForm,
                        'poller': poller,
                        'config': config
                    }), Events.onActionableResponseEvent);

                    EventDispatcher.getInstance().dispatch(new StateChangeEvent(null, {
                        'state': {loading: false, formSubmitted: false},
                    }), Events.onStateChangeEvent);
                } else {
                    // @todo 3dsv2 catch errors and handle accordingly
                    poller.clear();
                }
            }).catch((exception) => {
                poller.clear();
            });
        }

        /**
         * @param {PaymentCanceledEvent} event
         */
        static onPaymentCanceledEvent(event) {

            console.log('onPaymentCanceledEvent fabriq ');

            EventDispatcher.getInstance().dispatch(new StateChangeEvent(event.getSubject(), {
                'state': {loading: false, formSubmitted: false},
            }), Events.onStateChangeEvent);

            EventDispatcher.getInstance().dispatch(new ModalCloseEvent(event), Events.onModalCloseEvent);
        }


        /**
         * @param {PaymentFailedEvent} event
         */
        static onPaymentFailedEvent(event) {
            if (event.subject instanceof ErrorResponse) {
                let response = event.subject;
                let state = State.getInstance();

                let invalidTargets = [];

                switch (response.getErrorCode()) {
                    case 2201:
                    case 2202:
                    case 2203:
                        invalidTargets.push(state.getElementFromReference(Events$1.creditCardNumber));
                        invalidTargets.push(state.getElementFromReference(Events$1.creditCardCvv));
                        invalidTargets.push(state.getElementFromReference(Events$1.creditCardExpirationMonth));
                        invalidTargets.push(state.getElementFromReference(Events$1.creditCardExpirationYear));
                        break;

                    case 2801:
                        invalidTargets.push(state.getElementFromReference(Events$1.creditCardCvv));
                        break;

                    case 2802:
                        invalidTargets.push(state.getElementFromReference(Events$1.creditCardExpirationMonth));
                        invalidTargets.push(state.getElementFromReference(Events$1.creditCardExpirationYear));
                        break;
                }

                invalidTargets.forEach(function (target) {
                    target.setAttribute('data-validation', 'invalid');
                });
            }

            let resolveEvent = new ResolveModalEvent(event.subject);
            EventDispatcher.getInstance().dispatch(resolveEvent, Events.onResolveModalEvent);

            let modalOpenEvent = new ModalOpenEvent(
                resolveEvent.getResolvedModal(),
                resolveEvent.getParameters()
            );

            EventDispatcher.getInstance().dispatch(new StateChangeEvent(event.getSubject(), {
                'state': {loading: false, formSubmitted: false},
            }), Events.onStateChangeEvent);

            EventDispatcher.getInstance().dispatch(modalOpenEvent, Events.onModalOpenEvent);
        }

        /**
         * When we are in the retry challenge state, modify the form action and data.
         * @param event
         */
        static onSubmitDataEvent(event)
        {
            let state = State.getInstance();

            if (!state.isChallengeRetry() || !state.isChallengeRetryValid()) {
                return;
            }

            let currentState = state.getCurrentState();

            event.setParameter('form_action', event.getParameter('authorization_url'));
            event.getSubject().set('transaction_id', currentState.transactionId);
            event.getSubject().set('entrance_code', currentState.entranceCode);
            event.getSubject().set('threeds_transaction_id', currentState.threeDSTransactionId);
            event.getSubject().set('acquirer_id', currentState.acquirerId);
        }

        /**
         * Build the return url
         *
         * @param event
         * @return {string}
         */
        static createReturnUrl(event)
        {
            let url = new URL(event.parameters.paymentCompleteUrl);
            let urlParameters = event.getParameter('urlParameters', {});

            try {
                Object.entries(urlParameters).forEach(([key, value]) => {
                    url.searchParams.set(key, value);
                });
            } catch (e) {
                // @todo fallback on default url?
            }

            return url.toString();
        }

        /**
         * Handle the actual redirection.
         *
         * @param event
         * @param url
         */
        static handleRedirection(event, url) {
            if (event.getParameter('redirection_enabled')) {
                let passedUrl = url;
                setTimeout(function(){
                    let finalUrl = null !== event.getParameter('redirection_url') ?
                        event.getParameter('redirection_url'):
                        passedUrl;
                    window.location.replace(finalUrl);
                }, event.getParameter('redirection_timeout'));
            }
        }
    }

    /**
     * Class that helps polling for the transaction status
     */
    class Poller {
        constructor(eventDispatcher, interval = 3500) {
            this.eventDispatcher = eventDispatcher;
            this.interval = null;
            this.intervalSeconds = interval;
        }

        poll(url, func) {
            this.clear(); // make sure there isn't any other polling instance.
            this.interval = setInterval(func, this.intervalSeconds, url);

            this.eventDispatcher.dispatch(new StateChangeEvent(this, {
                'state': {isPolling: true}
            }), Events.onStateChangeEvent);
        }

        clear() {
            if (null == this.interval) {
                return;
            }

            clearInterval(this.interval);

            this.eventDispatcher.dispatch(new StateChangeEvent(this, {
                'state': {isPolling: false}
            }), Events.onStateChangeEvent);
        }

        getInterval() {
            return this.interval;
        }

        hasTimeout() {
            return this.interval != null;
        }
    }

    class ThreeDSTransactionStatus {
        static get success()
        {
            return 100;
        }

        static get informational()
        {
            return 80;
        }

        static get authenticationStarted()
        {
            return 30;
        }

        static get pending()
        {
            return 10;
        }

        static get failed()
        {
            return -10;
        }

        static get canceled()
        {
            return -5;
        }
    }

    class PollingListener extends EventListener {
        constructor(poller) {
            super();
            this.poller = poller;
        }

        /**
         * @param {PollingResponseEvent} event
         */
        onPollingResponse(event) {
            let pollingResponse = event.subject;

            try {

                if (!pollingResponse.getTransactionStatusCode()) {
                    this.poller.clear();
                }

                let statusCode = pollingResponse.getTransactionStatusCode();
                let state = State.getInstance();
                let currentState = state.getCurrentState();

                console.log('kees code:  ' + statusCode);

                if (currentState.isPaid) {
                    console.log('TIS BEtAAAALD');
                    return;
                } else {
                    console.log('TIS niet betaald');
                }

                // In case of TDS, we need to catch this separately.
                if (statusCode > ThreeDSTransactionStatus.pending
                    && statusCode < ThreeDSTransactionStatus.authenticationStarted
                    && false === currentState.isAuthenticatingTds
                )
                {
                    console.log('dispagin onPaymentAuthenticateEvent');
                    EventDispatcher.getInstance().dispatch(event, Events.onPaymentAuthenticateEvent);
                }

                switch (statusCode) {
                    // Authentication cleared, proceed with authorization.
                    case ThreeDSTransactionStatus.success:
                        this.poller.clear();
                        EventDispatcher.getInstance().dispatch(new PaymentAuthorizationEvent(pollingResponse, {
                            'pollingEvent': event
                        }), Events.onPaymentAuthorizeEvent);
                        break;
                    case ThreeDSTransactionStatus.failed:
                        this.poller.clear();
                        EventDispatcher.getInstance().dispatch(new PaymentFailedEvent(pollingResponse), Events.onPaymentFailedEvent);
                        break;
                    case ThreeDSTransactionStatus.canceled:
                        this.poller.clear();
                        EventDispatcher.getInstance().dispatch(new PaymentCanceledEvent(pollingResponse), Events.onPaymentCanceledEvent);
                        break;
                    case null:
                        console.log('val is null');

                        EventDispatcher.getInstance().dispatch(new PaymentCanceledEvent(pollingResponse), Events.onPaymentCanceledEvent);

                        this.poller.clear();
                        break;
                    default:
                        console.log('Default cases. statusCode is: ' + statusCode);
                        break;
                }

            } catch (exception) {

                console.log(exception);
                console.log('foute status code');
                //EventDispatcher.getInstance().dispatch(new PaymentCanceledEvent(pollingResponse), Events.onPaymentCanceledEvent);
                this.poller.clear();

                throw exception;
            }
        }

        onStopPolling(event) {
            // Don't stop polling if the ChallengeModal closes without user interaction.
            if (event instanceof ModalCloseEvent
                && event.getSubject() instanceof ChallengeModal
                && !event.hasParameter('manual')
            ) {
                return;
            }

            this.poller.clear();
        }
    }

    class ResolveModalListener extends EventListener {
        /**
         * @param {ResolveModalEvent} event
         */
        static onResolveModal(event)
        {
            let response = event.subject;
            let modal = null;

            if (response instanceof PollingResponse
                && response.getTransactionStatusCode() === ThreeDSTransactionStatus.success
            ) {
                modal = new AuthorizingModal(response);
            }

            if (response instanceof ChallengedResponse && response.isIFrameChallenge()) {
                modal = new ChallengeModal(response);
            }

            if (response instanceof ErrorResponse) {
                modal = new ErrorModal(response);
            }

            if (response instanceof PaymentCompleteResponse) {
                modal = new PaymentCompleteModal(response);
            }

            if (!modal && !(response instanceof TdsMethodResponse)) {
                throw new Error('Could not resolve modal');
            }

            event.setResolvedModal(modal);
        }
    }

    class ResponseFactoryEvent extends GenericEvent {}

    class ResponseFactoryListener extends EventListener {
        /**
         * @param {ResponseEvent} event
         */
        static onResponseEvent(event) {
            let responseFactory = new ResponseFactory();

            event.setParameter('response', responseFactory.createResponse(event.subject));

            let form = State.getInstance().getElementFromReference(Events$1.form);

            EventDispatcher.getInstance().dispatch(new StateChangeEvent(form, {
                'validation': null,
                'state': {loading: false},
            }), Events.onStateChangeEvent);
        }
    }

    class ResponseToJsonListener extends EventListener {
        /**
         * @param {ResponseEvent} event
         */
        static onResponseEvent(event) {
            event.subject = event.subject.json();
        }
    }

    class StateChangeListener extends EventListener {
        /**
         * @param {StateChangeEvent} event
         */
        static onStateChangeEvent(event) {
            let state = State.getInstance();

            if ('state' in event.parameters) {
                state.updateState(event.parameters.state);

                let supplementalStateChecks = {
                    inputComplete: StateChangeListener.inputComplete(state),
                    formReadyForSubmission: StateChangeListener.readyForSubmission(state),
                };

                state.updateState(supplementalStateChecks);
            }
        }

        /**
         * @param {ResponseFactoryEvent} event
         */
        static onResponseFactoryEvent(event) {
            if (!(event instanceof ResponseFactoryEvent)) {
                return;
            }

            let response = event.hasParameter('response') ? event.getParameter('response') : null;

            if (!response) {
                throw Error('Unable to update state, no response given.');
            }

            if (!response.getTransactionId() || !response.getThreeDSTransactionId()) {
                return;
            }

            StateChangeListener.onStateChangeEvent(new StateChangeEvent(event, {
                'state': {
                    transactionId: response.getTransactionId(),
                    entranceCode: response.getEntranceCode(),
                    threeDSTransactionId: response.getThreeDSTransactionId(),
                    acquirerId: response.getAcquirerId()
                },
            }));
        }

        /**
         * @param {SubmitDataEvent} event
         */
        static onSubmitDataEvent(event)
        {
            let formData = event.getSubject();

            StateChangeListener.onStateChangeEvent(new StateChangeEvent(event, {
                'state': {payload: formData.get('pay_encrypted_data')}
            }));
        }

        /**
         * @param {PaymentCompleteEvent} event
         */
        static onPaymentCompleteEvent(event) {
            StateChangeListener.onStateChangeEvent(new StateChangeEvent(event, {
                'validation': null,
                'state': {loading: false},
            }));
        }

        /**
         * @param {PaymentFailedEvent} event
         */
        static onPaymentFailedEvent(event) {
            StateChangeListener.onStateChangeEvent(new StateChangeEvent(event, {
                'validation': null,
                'state': {loading: false},
            }));
        }

        static readyForSubmission(state) {
            return StateChangeListener.inputComplete(state);
        }

        static inputComplete(state) {
            return state.getStateParameter('cardHolderInputComplete') &&
                state.getStateParameter('cardNumberInputComplete') &&
                state.getStateParameter('cardCvvInputComplete') &&
                state.getStateParameter('cardExpiryMonthInputComplete') &&
                state.getStateParameter('cardExpiryYearInputComplete');
        }
    }

    class SubmitDataEvent extends GenericEvent {}

    var cardTypes = {
        visa: {
            niceType: "Visa",
            type: "visa",
            patterns: [4],
            gaps: [4, 8, 12],
            lengths: [16, 18, 19],
            code: {
                name: "CVV",
                size: 3,
            },
        },
        mastercard: {
            niceType: "Mastercard",
            type: "mastercard",
            patterns: [[51, 55], [2221, 2229], [223, 229], [23, 26], [270, 271], 2720],
            gaps: [4, 8, 12],
            lengths: [16],
            code: {
                name: "CVC",
                size: 3,
            },
        },
        "american-express": {
            niceType: "American Express",
            type: "american-express",
            patterns: [34, 37],
            gaps: [4, 10],
            lengths: [15],
            code: {
                name: "CID",
                size: 4,
            },
        },
        "diners-club": {
            niceType: "Diners Club",
            type: "diners-club",
            patterns: [[300, 305], 36, 38, 39],
            gaps: [4, 10],
            lengths: [14, 16, 19],
            code: {
                name: "CVV",
                size: 3,
            },
        },
        discover: {
            niceType: "Discover",
            type: "discover",
            patterns: [6011, [644, 649], 65],
            gaps: [4, 8, 12],
            lengths: [16, 19],
            code: {
                name: "CID",
                size: 3,
            },
        },
        jcb: {
            niceType: "JCB",
            type: "jcb",
            patterns: [2131, 1800, [3528, 3589]],
            gaps: [4, 8, 12],
            lengths: [16, 17, 18, 19],
            code: {
                name: "CVV",
                size: 3,
            },
        },
        unionpay: {
            niceType: "UnionPay",
            type: "unionpay",
            patterns: [
                620,
                [624, 626],
                [62100, 62182],
                [62184, 62187],
                [62185, 62197],
                [62200, 62205],
                [622010, 622999],
                622018,
                [622019, 622999],
                [62207, 62209],
                [622126, 622925],
                [623, 626],
                6270,
                6272,
                6276,
                [627700, 627779],
                [627781, 627799],
                [6282, 6289],
                6291,
                6292,
                810,
                [8110, 8131],
                [8132, 8151],
                [8152, 8163],
                [8164, 8171],
            ],
            gaps: [4, 8, 12],
            lengths: [14, 15, 16, 17, 18, 19],
            code: {
                name: "CVN",
                size: 3,
            },
        },
        maestro: {
            niceType: "Maestro",
            type: "maestro",
            patterns: [
                493698,
                [500000, 504174],
                [504176, 506698],
                [506779, 508999],
                [56, 59],
                63,
                67,
                6,
            ],
            gaps: [4, 8, 12],
            lengths: [12, 13, 14, 15, 16, 17, 18, 19],
            code: {
                name: "CVC",
                size: 3,
            },
        },
        elo: {
            niceType: "Elo",
            type: "elo",
            patterns: [
                401178,
                401179,
                438935,
                457631,
                457632,
                431274,
                451416,
                457393,
                504175,
                [506699, 506778],
                [509000, 509999],
                627780,
                636297,
                636368,
                [650031, 650033],
                [650035, 650051],
                [650405, 650439],
                [650485, 650538],
                [650541, 650598],
                [650700, 650718],
                [650720, 650727],
                [650901, 650978],
                [651652, 651679],
                [655000, 655019],
                [655021, 655058],
            ],
            gaps: [4, 8, 12],
            lengths: [16],
            code: {
                name: "CVE",
                size: 3,
            },
        },
        mir: {
            niceType: "Mir",
            type: "mir",
            patterns: [[2200, 2204]],
            gaps: [4, 8, 12],
            lengths: [16, 17, 18, 19],
            code: {
                name: "CVP2",
                size: 3,
            },
        },
        hiper: {
            niceType: "Hiper",
            type: "hiper",
            patterns: [637095, 637568, 637599, 637609, 637612],
            gaps: [4, 8, 12],
            lengths: [16],
            code: {
                name: "CVC",
                size: 3,
            },
        },
        hipercard: {
            niceType: "Hipercard",
            type: "hipercard",
            patterns: [606282],
            gaps: [4, 8, 12],
            lengths: [16],
            code: {
                name: "CVC",
                size: 3,
            },
        },
    };
    var cardTypes_1 = cardTypes;

    var clone_1 = createCommonjsModule(function (module, exports) {
        Object.defineProperty(exports, "__esModule", { value: true });
        exports.clone = void 0;
        function clone(originalObject) {
            if (!originalObject) {
                return null;
            }
            return JSON.parse(JSON.stringify(originalObject));
        }
        exports.clone = clone;
    });

    unwrapExports(clone_1);
    clone_1.clone;

    var matches_1 = createCommonjsModule(function (module, exports) {
        /*
     * Adapted from https://github.com/polvo-labs/card-type/blob/aaab11f80fa1939bccc8f24905a06ae3cd864356/src/cardType.js#L37-L42
     * */
        Object.defineProperty(exports, "__esModule", { value: true });
        exports.matches = void 0;
        function matchesRange(cardNumber, min, max) {
            var maxLengthToCheck = String(min).length;
            var substr = cardNumber.substr(0, maxLengthToCheck);
            var integerRepresentationOfCardNumber = parseInt(substr, 10);
            min = parseInt(String(min).substr(0, substr.length), 10);
            max = parseInt(String(max).substr(0, substr.length), 10);
            return (integerRepresentationOfCardNumber >= min &&
                integerRepresentationOfCardNumber <= max);
        }
        function matchesPattern(cardNumber, pattern) {
            pattern = String(pattern);
            return (pattern.substring(0, cardNumber.length) ===
                cardNumber.substring(0, pattern.length));
        }
        function matches(cardNumber, pattern) {
            if (Array.isArray(pattern)) {
                return matchesRange(cardNumber, pattern[0], pattern[1]);
            }
            return matchesPattern(cardNumber, pattern);
        }
        exports.matches = matches;
    });

    unwrapExports(matches_1);
    matches_1.matches;

    var addMatchingCardsToResults_1 = createCommonjsModule(function (module, exports) {
        Object.defineProperty(exports, "__esModule", { value: true });
        exports.addMatchingCardsToResults = void 0;


        function addMatchingCardsToResults(cardNumber, cardConfiguration, results) {
            var i, patternLength;
            for (i = 0; i < cardConfiguration.patterns.length; i++) {
                var pattern = cardConfiguration.patterns[i];
                if (!matches_1.matches(cardNumber, pattern)) {
                    continue;
                }
                var clonedCardConfiguration = clone_1.clone(cardConfiguration);
                if (Array.isArray(pattern)) {
                    patternLength = String(pattern[0]).length;
                }
                else {
                    patternLength = String(pattern).length;
                }
                if (cardNumber.length >= patternLength) {
                    clonedCardConfiguration.matchStrength = patternLength;
                }
                results.push(clonedCardConfiguration);
                break;
            }
        }
        exports.addMatchingCardsToResults = addMatchingCardsToResults;
    });

    unwrapExports(addMatchingCardsToResults_1);
    addMatchingCardsToResults_1.addMatchingCardsToResults;

    var isValidInputType_1 = createCommonjsModule(function (module, exports) {
        Object.defineProperty(exports, "__esModule", { value: true });
        exports.isValidInputType = void 0;
        function isValidInputType(cardNumber) {
            return typeof cardNumber === "string" || cardNumber instanceof String;
        }
        exports.isValidInputType = isValidInputType;
    });

    unwrapExports(isValidInputType_1);
    isValidInputType_1.isValidInputType;

    var findBestMatch_1 = createCommonjsModule(function (module, exports) {
        Object.defineProperty(exports, "__esModule", { value: true });
        exports.findBestMatch = void 0;
        function hasEnoughResultsToDetermineBestMatch(results) {
            var numberOfResultsWithMaxStrengthProperty = results.filter(function (result) { return result.matchStrength; }).length;
            /*
         * if all possible results have a maxStrength property that means the card
         * number is sufficiently long enough to determine conclusively what the card
         * type is
         * */
            return (numberOfResultsWithMaxStrengthProperty > 0 &&
                numberOfResultsWithMaxStrengthProperty === results.length);
        }
        function findBestMatch(results) {
            if (!hasEnoughResultsToDetermineBestMatch(results)) {
                return null;
            }
            return results.reduce(function (bestMatch, result) {
                if (!bestMatch) {
                    return result;
                }
                /*
             * If the current best match pattern is less specific than this result, set
             * the result as the new best match
             * */
                if (Number(bestMatch.matchStrength) < Number(result.matchStrength)) {
                    return result;
                }
                return bestMatch;
            });
        }
        exports.findBestMatch = findBestMatch;
    });

    unwrapExports(findBestMatch_1);
    findBestMatch_1.findBestMatch;

    var __assign = (commonjsGlobal && commonjsGlobal.__assign) || function () {
        __assign = Object.assign || function(t) {
            for (var s, i = 1, n = arguments.length; i < n; i++) {
                s = arguments[i];
                for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                    t[p] = s[p];
            }
            return t;
        };
        return __assign.apply(this, arguments);
    };





    var customCards = {};
    var cardNames = {
        VISA: "visa",
        MASTERCARD: "mastercard",
        AMERICAN_EXPRESS: "american-express",
        DINERS_CLUB: "diners-club",
        DISCOVER: "discover",
        JCB: "jcb",
        UNIONPAY: "unionpay",
        MAESTRO: "maestro",
        ELO: "elo",
        MIR: "mir",
        HIPER: "hiper",
        HIPERCARD: "hipercard",
    };
    var ORIGINAL_TEST_ORDER = [
        cardNames.VISA,
        cardNames.MASTERCARD,
        cardNames.AMERICAN_EXPRESS,
        cardNames.DINERS_CLUB,
        cardNames.DISCOVER,
        cardNames.JCB,
        cardNames.UNIONPAY,
        cardNames.MAESTRO,
        cardNames.ELO,
        cardNames.MIR,
        cardNames.HIPER,
        cardNames.HIPERCARD,
    ];
    var testOrder = clone_1.clone(ORIGINAL_TEST_ORDER);
    function findType(cardType) {
        return customCards[cardType] || cardTypes_1[cardType];
    }
    function getAllCardTypes() {
        return testOrder.map(function (cardType) { return clone_1.clone(findType(cardType)); });
    }
    function getCardPosition(name, ignoreErrorForNotExisting) {
        if (ignoreErrorForNotExisting === void 0) { ignoreErrorForNotExisting = false; }
        var position = testOrder.indexOf(name);
        if (!ignoreErrorForNotExisting && position === -1) {
            throw new Error('"' + name + '" is not a supported card type.');
        }
        return position;
    }
    function creditCardType(cardNumber) {
        var results = [];
        if (!isValidInputType_1.isValidInputType(cardNumber)) {
            return results;
        }
        if (cardNumber.length === 0) {
            return getAllCardTypes();
        }
        testOrder.forEach(function (cardType) {
            var cardConfiguration = findType(cardType);
            addMatchingCardsToResults_1.addMatchingCardsToResults(cardNumber, cardConfiguration, results);
        });
        var bestMatch = findBestMatch_1.findBestMatch(results);
        if (bestMatch) {
            return [bestMatch];
        }
        return results;
    }
    creditCardType.getTypeInfo = function (cardType) {
        return clone_1.clone(findType(cardType));
    };
    creditCardType.removeCard = function (name) {
        var position = getCardPosition(name);
        testOrder.splice(position, 1);
    };
    creditCardType.addCard = function (config) {
        var existingCardPosition = getCardPosition(config.type, true);
        customCards[config.type] = config;
        if (existingCardPosition === -1) {
            testOrder.push(config.type);
        }
    };
    creditCardType.updateCard = function (cardType, updates) {
        var originalObject = customCards[cardType] || cardTypes_1[cardType];
        if (!originalObject) {
            throw new Error("\"" + cardType + "\" is not a recognized type. Use `addCard` instead.'");
        }
        if (updates.type && originalObject.type !== updates.type) {
            throw new Error("Cannot overwrite type parameter.");
        }
        var clonedCard = clone_1.clone(originalObject);
        clonedCard = __assign(__assign({}, clonedCard), updates);
        customCards[clonedCard.type] = clonedCard;
    };
    creditCardType.changeOrder = function (name, position) {
        var currentPosition = getCardPosition(name);
        testOrder.splice(currentPosition, 1);
        testOrder.splice(position, 0, name);
    };
    creditCardType.resetModifications = function () {
        testOrder = clone_1.clone(ORIGINAL_TEST_ORDER);
        customCards = {};
    };
    creditCardType.types = cardNames;
    var dist$1 = creditCardType;

    var cardholderName_1 = createCommonjsModule(function (module, exports) {
        Object.defineProperty(exports, "__esModule", { value: true });
        exports.cardholderName = void 0;
        var CARD_NUMBER_REGEX = /^[\d\s-]*$/;
        var MAX_LENGTH = 255;
        function verification(isValid, isPotentiallyValid) {
            return { isValid: isValid, isPotentiallyValid: isPotentiallyValid };
        }
        function cardholderName(value) {
            if (typeof value !== "string") {
                return verification(false, false);
            }
            if (value.length === 0) {
                return verification(false, true);
            }
            if (value.length > MAX_LENGTH) {
                return verification(false, false);
            }
            if (CARD_NUMBER_REGEX.test(value)) {
                return verification(false, true);
            }
            return verification(true, true);
        }
        exports.cardholderName = cardholderName;
    });

    unwrapExports(cardholderName_1);
    cardholderName_1.cardholderName;

    /* eslint-disable */
    function luhn10(identifier) {
        var sum = 0;
        var alt = false;
        var i = identifier.length - 1;
        var num;
        while (i >= 0) {
            num = parseInt(identifier.charAt(i), 10);
            if (alt) {
                num *= 2;
                if (num > 9) {
                    num = (num % 10) + 1; // eslint-disable-line no-extra-parens
                }
            }
            alt = !alt;
            sum += num;
            i--;
        }
        return sum % 10 === 0;
    }
    var luhn10_1 = luhn10;

    var cardNumber_1 = createCommonjsModule(function (module, exports) {
        Object.defineProperty(exports, "__esModule", { value: true });
        exports.cardNumber = void 0;


        function verification(card, isPotentiallyValid, isValid) {
            return {
                card: card,
                isPotentiallyValid: isPotentiallyValid,
                isValid: isValid,
            };
        }
        function cardNumber(value, options) {
            if (options === void 0) { options = {}; }
            var isPotentiallyValid, isValid, maxLength;
            if (typeof value !== "string" && typeof value !== "number") {
                return verification(null, false, false);
            }
            var testCardValue = String(value).replace(/-|\s/g, "");
            if (!/^\d*$/.test(testCardValue)) {
                return verification(null, false, false);
            }
            var potentialTypes = dist$1(testCardValue);
            if (potentialTypes.length === 0) {
                return verification(null, false, false);
            }
            else if (potentialTypes.length !== 1) {
                return verification(null, true, false);
            }
            var cardType = potentialTypes[0];
            if (options.maxLength && testCardValue.length > options.maxLength) {
                return verification(cardType, false, false);
            }
            if (cardType.type === dist$1.types.UNIONPAY &&
                options.luhnValidateUnionPay !== true) {
                isValid = true;
            }
            else {
                isValid = luhn10_1(testCardValue);
            }
            maxLength = Math.max.apply(null, cardType.lengths);
            if (options.maxLength) {
                maxLength = Math.min(options.maxLength, maxLength);
            }
            for (var i = 0; i < cardType.lengths.length; i++) {
                if (cardType.lengths[i] === testCardValue.length) {
                    isPotentiallyValid = testCardValue.length < maxLength || isValid;
                    return verification(cardType, isPotentiallyValid, isValid);
                }
            }
            return verification(cardType, testCardValue.length < maxLength, false);
        }
        exports.cardNumber = cardNumber;
    });

    unwrapExports(cardNumber_1);
    cardNumber_1.cardNumber;

    var expirationYear_1 = createCommonjsModule(function (module, exports) {
        Object.defineProperty(exports, "__esModule", { value: true });
        exports.expirationYear = void 0;
        var DEFAULT_VALID_NUMBER_OF_YEARS_IN_THE_FUTURE = 19;
        function verification(isValid, isPotentiallyValid, isCurrentYear) {
            return {
                isValid: isValid,
                isPotentiallyValid: isPotentiallyValid,
                isCurrentYear: isCurrentYear || false,
            };
        }
        function expirationYear(value, maxElapsedYear) {
            if (maxElapsedYear === void 0) { maxElapsedYear = DEFAULT_VALID_NUMBER_OF_YEARS_IN_THE_FUTURE; }
            var isCurrentYear;
            if (typeof value !== "string") {
                return verification(false, false);
            }
            if (value.replace(/\s/g, "") === "") {
                return verification(false, true);
            }
            if (!/^\d*$/.test(value)) {
                return verification(false, false);
            }
            var len = value.length;
            if (len < 2) {
                return verification(false, true);
            }
            var currentYear = new Date().getFullYear();
            if (len === 3) {
                // 20x === 20x
                var firstTwo = value.slice(0, 2);
                var currentFirstTwo = String(currentYear).slice(0, 2);
                return verification(false, firstTwo === currentFirstTwo);
            }
            if (len > 4) {
                return verification(false, false);
            }
            var numericValue = parseInt(value, 10);
            var twoDigitYear = Number(String(currentYear).substr(2, 2));
            var valid = false;
            if (len === 2) {
                isCurrentYear = twoDigitYear === numericValue;
                valid =
                    numericValue >= twoDigitYear &&
                    numericValue <= twoDigitYear + maxElapsedYear;
            }
            else if (len === 4) {
                isCurrentYear = currentYear === numericValue;
                valid =
                    numericValue >= currentYear &&
                    numericValue <= currentYear + maxElapsedYear;
            }
            return verification(valid, valid, isCurrentYear);
        }
        exports.expirationYear = expirationYear;
    });

    unwrapExports(expirationYear_1);
    expirationYear_1.expirationYear;

    var isArray = createCommonjsModule(function (module, exports) {
        // Polyfill taken from <https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/isArray#Polyfill>.
        Object.defineProperty(exports, "__esModule", { value: true });
        exports.isArray = void 0;
        exports.isArray = Array.isArray ||
            function (arg) {
                return Object.prototype.toString.call(arg) === "[object Array]";
            };
    });

    unwrapExports(isArray);
    isArray.isArray;

    var parseDate_1 = createCommonjsModule(function (module, exports) {
        Object.defineProperty(exports, "__esModule", { value: true });
        exports.parseDate = void 0;


        function getNumberOfMonthDigitsInDateString(dateString) {
            var firstCharacter = Number(dateString[0]);
            var assumedYear;
            /*
          if the first character in the string starts with `0`,
          we know that the month will be 2 digits.

          '0122' => {month: '01', year: '22'}
        */
            if (firstCharacter === 0) {
                return 2;
            }
            /*
          if the first character in the string starts with
          number greater than 1, it must be a 1 digit month

          '322' => {month: '3', year: '22'}
        */
            if (firstCharacter > 1) {
                return 1;
            }
            /*
          if the first 2 characters make up a number between
          13-19, we know that the month portion must be 1

          '139' => {month: '1', year: '39'}
        */
            if (firstCharacter === 1 && Number(dateString[1]) > 2) {
                return 1;
            }
            /*
          if the first 2 characters make up a number between
          10-12, we check if the year portion would be considered
          valid if we assumed that the month was 1. If it is
          not potentially valid, we assume the month must have
          2 digits.

          '109' => {month: '10', year: '9'}
          '120' => {month: '1', year: '20'} // when checked in the year 2019
          '120' => {month: '12', year: '0'} // when checked in the year 2021
        */
            if (firstCharacter === 1) {
                assumedYear = dateString.substr(1);
                return expirationYear_1.expirationYear(assumedYear).isPotentiallyValid ? 1 : 2;
            }
            /*
          If the length of the value is exactly 5 characters,
          we assume a full year was passed in, meaning the remaining
          single leading digit must be the month value.

          '12202' => {month: '1', year: '2202'}
        */
            if (dateString.length === 5) {
                return 1;
            }
            /*
          If the length of the value is more than five characters,
          we assume a full year was passed in addition to the month
          and therefore the month portion must be 2 digits.

          '112020' => {month: '11', year: '2020'}
        */
            if (dateString.length > 5) {
                return 2;
            }
            /*
          By default, the month value is the first value
        */
            return 1;
        }
        function parseDate(datestring) {
            var date;
            if (/^\d{4}-\d{1,2}$/.test(datestring)) {
                date = datestring.split("-").reverse();
            }
            else if (/\//.test(datestring)) {
                date = datestring.split(/\s*\/\s*/g);
            }
            else if (/\s/.test(datestring)) {
                date = datestring.split(/ +/g);
            }
            if (isArray.isArray(date)) {
                return {
                    month: date[0] || "",
                    year: date.slice(1).join(),
                };
            }
            var numberOfDigitsInMonth = getNumberOfMonthDigitsInDateString(datestring);
            var month = datestring.substr(0, numberOfDigitsInMonth);
            return {
                month: month,
                year: datestring.substr(month.length),
            };
        }
        exports.parseDate = parseDate;
    });

    unwrapExports(parseDate_1);
    parseDate_1.parseDate;

    var expirationMonth_1 = createCommonjsModule(function (module, exports) {
        Object.defineProperty(exports, "__esModule", { value: true });
        exports.expirationMonth = void 0;
        function verification(isValid, isPotentiallyValid, isValidForThisYear) {
            return {
                isValid: isValid,
                isPotentiallyValid: isPotentiallyValid,
                isValidForThisYear: isValidForThisYear || false,
            };
        }
        function expirationMonth(value) {
            var currentMonth = new Date().getMonth() + 1;
            if (typeof value !== "string") {
                return verification(false, false);
            }
            if (value.replace(/\s/g, "") === "" || value === "0") {
                return verification(false, true);
            }
            if (!/^\d*$/.test(value)) {
                return verification(false, false);
            }
            var month = parseInt(value, 10);
            if (isNaN(Number(value))) {
                return verification(false, false);
            }
            var result = month > 0 && month < 13;
            return verification(result, result, result && month >= currentMonth);
        }
        exports.expirationMonth = expirationMonth;
    });

    unwrapExports(expirationMonth_1);
    expirationMonth_1.expirationMonth;

    var expirationDate_1 = createCommonjsModule(function (module, exports) {
        var __assign = (commonjsGlobal && commonjsGlobal.__assign) || function () {
            __assign = Object.assign || function(t) {
                for (var s, i = 1, n = arguments.length; i < n; i++) {
                    s = arguments[i];
                    for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                        t[p] = s[p];
                }
                return t;
            };
            return __assign.apply(this, arguments);
        };
        Object.defineProperty(exports, "__esModule", { value: true });
        exports.expirationDate = void 0;



        function verification(isValid, isPotentiallyValid, month, year) {
            return {
                isValid: isValid,
                isPotentiallyValid: isPotentiallyValid,
                month: month,
                year: year,
            };
        }
        function expirationDate(value, maxElapsedYear) {
            var date;
            if (typeof value === "string") {
                value = value.replace(/^(\d\d) (\d\d(\d\d)?)$/, "$1/$2");
                date = parseDate_1.parseDate(String(value));
            }
            else if (value !== null && typeof value === "object") {
                var fullDate = __assign({}, value);
                date = {
                    month: String(fullDate.month),
                    year: String(fullDate.year),
                };
            }
            else {
                return verification(false, false, null, null);
            }
            var monthValid = expirationMonth_1.expirationMonth(date.month);
            var yearValid = expirationYear_1.expirationYear(date.year, maxElapsedYear);
            if (monthValid.isValid) {
                if (yearValid.isCurrentYear) {
                    var isValidForThisYear = monthValid.isValidForThisYear;
                    return verification(isValidForThisYear, isValidForThisYear, date.month, date.year);
                }
                if (yearValid.isValid) {
                    return verification(true, true, date.month, date.year);
                }
            }
            if (monthValid.isPotentiallyValid && yearValid.isPotentiallyValid) {
                return verification(false, true, null, null);
            }
            return verification(false, false, null, null);
        }
        exports.expirationDate = expirationDate;
    });

    unwrapExports(expirationDate_1);
    expirationDate_1.expirationDate;

    var cvv_1 = createCommonjsModule(function (module, exports) {
        Object.defineProperty(exports, "__esModule", { value: true });
        exports.cvv = void 0;
        var DEFAULT_LENGTH = 3;
        function includes(array, thing) {
            for (var i = 0; i < array.length; i++) {
                if (thing === array[i]) {
                    return true;
                }
            }
            return false;
        }
        function max(array) {
            var maximum = DEFAULT_LENGTH;
            var i = 0;
            for (; i < array.length; i++) {
                maximum = array[i] > maximum ? array[i] : maximum;
            }
            return maximum;
        }
        function verification(isValid, isPotentiallyValid) {
            return { isValid: isValid, isPotentiallyValid: isPotentiallyValid };
        }
        function cvv(value, maxLength) {
            if (maxLength === void 0) { maxLength = DEFAULT_LENGTH; }
            maxLength = maxLength instanceof Array ? maxLength : [maxLength];
            if (typeof value !== "string") {
                return verification(false, false);
            }
            if (!/^\d*$/.test(value)) {
                return verification(false, false);
            }
            if (includes(maxLength, value.length)) {
                return verification(true, true);
            }
            if (value.length < Math.min.apply(null, maxLength)) {
                return verification(false, true);
            }
            if (value.length > max(maxLength)) {
                return verification(false, false);
            }
            return verification(true, true);
        }
        exports.cvv = cvv;
    });

    unwrapExports(cvv_1);
    cvv_1.cvv;

    var postalCode_1 = createCommonjsModule(function (module, exports) {
        Object.defineProperty(exports, "__esModule", { value: true });
        exports.postalCode = void 0;
        var DEFAULT_MIN_POSTAL_CODE_LENGTH = 3;
        function verification(isValid, isPotentiallyValid) {
            return { isValid: isValid, isPotentiallyValid: isPotentiallyValid };
        }
        function postalCode(value, options) {
            if (options === void 0) { options = {}; }
            var minLength = options.minLength || DEFAULT_MIN_POSTAL_CODE_LENGTH;
            if (typeof value !== "string") {
                return verification(false, false);
            }
            else if (value.length < minLength) {
                return verification(false, true);
            }
            return verification(true, true);
        }
        exports.postalCode = postalCode;
    });

    unwrapExports(postalCode_1);
    postalCode_1.postalCode;

    var dist = createCommonjsModule(function (module) {
        var __createBinding = (commonjsGlobal && commonjsGlobal.__createBinding) || (Object.create ? (function(o, m, k, k2) {
            if (k2 === undefined) k2 = k;
            Object.defineProperty(o, k2, { enumerable: true, get: function() { return m[k]; } });
        }) : (function(o, m, k, k2) {
            if (k2 === undefined) k2 = k;
            o[k2] = m[k];
        }));
        var __setModuleDefault = (commonjsGlobal && commonjsGlobal.__setModuleDefault) || (Object.create ? (function(o, v) {
            Object.defineProperty(o, "default", { enumerable: true, value: v });
        }) : function(o, v) {
            o["default"] = v;
        });
        var __importStar = (commonjsGlobal && commonjsGlobal.__importStar) || function (mod) {
            if (mod && mod.__esModule) return mod;
            var result = {};
            if (mod != null) for (var k in mod) if (k !== "default" && Object.hasOwnProperty.call(mod, k)) __createBinding(result, mod, k);
            __setModuleDefault(result, mod);
            return result;
        };
        var creditCardType = __importStar(dist$1);







        var cardValidator = {
            creditCardType: creditCardType,
            cardholderName: cardholderName_1.cardholderName,
            number: cardNumber_1.cardNumber,
            expirationDate: expirationDate_1.expirationDate,
            expirationMonth: expirationMonth_1.expirationMonth,
            expirationYear: expirationYear_1.expirationYear,
            cvv: cvv_1.cvv,
            postalCode: postalCode_1.postalCode,
        };
        module.exports = cardValidator;
    });

    var cardValidator = unwrapExports(dist);

    class EncryptedForm {
        /**
         * Setup the PAY. CSE form.
         * @param config
         */
        constructor(config) {
            let defaults = {
                'debug': false,
                'refresh_url': null,
                'status_url': null,
                'authentication_url': null,
                'authorization_url': null,
                'post_url': null,
                'payment_complete_url': null,
                'form_input_payload_name': 'pay_encrypted_data',
                'form_selector': 'data-pay-encrypt-form',
                'field_selector': 'data-pay-encrypt-field',
                'field_value_reader': 'name',
                'public_keys': [],
                'language': 'NL',
                'merchant_identifier': null,
                'html5_enhanced_validation': true,
                'default_cvv_label': 'CVC',
                'default_card_number_length': 19,
                'bind': {
                    'submit': true
                },
                'icons': {
                    'creditcard': {
                        'default': 'img/creditcard/cc-front.svg',
                        'alipay': 'img/creditcard/cc-alipay.svg',
                        'american-express': 'img/creditcard/cc-amex.svg',
                        'diners-club': 'img/creditcard/cc-diners-club.svg',
                        'discover': 'img/creditcard/cc-discover.svg',
                        'elo': 'img/creditcard/cc-elo.svg',
                        'hiper': 'img/creditcard/cc-hiper.svg',
                        'hipercard': 'img/creditcard/cc-hipercard.svg',
                        'jcb': 'img/creditcard/cc-jcb.svg',
                        'maestro': 'img/creditcard/cc-maestro.svg',
                        'mastercard': 'img/creditcard/cc-mastercard.svg',
                        'mir': 'img/creditcard/cc-mir.svg',
                        'unionpay': 'img/creditcard/cc-unionpay.svg',
                        'visa': 'img/creditcard/cc-visa.svg'
                    }
                }
            };

            this.urlHelper = new UrlHelper();

            if (undefined !== config['public_keys']) {
                config['public_keys'] = PublicKeyFactory.fromJson(config['public_keys']);
            }

            this.config = Object.assign({}, defaults, config);
            this.config['refresh_url'] = this.urlHelper.ensureAbsoluteUrl(this.config['refresh_url']);
            this.config['status_url'] = this.urlHelper.ensureAbsoluteUrl(this.config['status_url']);
            this.config['authentication_url'] = this.urlHelper.ensureAbsoluteUrl(this.config['authentication_url']);
            this.config['authorization_url'] = this.urlHelper.ensureAbsoluteUrl(this.config['authorization_url']);
            this.config['post_url'] = this.urlHelper.ensureAbsoluteUrl(this.config['post_url']);
            this.config['payment_complete_url'] = this.urlHelper.ensureAbsoluteUrl(this.config['payment_complete_url']);

            for (const [cardType, image] of Object.entries(this.config.icons.creditcard)) {
                this.config.icons.creditcard[cardType] = this.urlHelper.ensureAbsoluteUrl(image);
            }

            this.checkSettings();
            this.createTdsMethodPlaceHolder();

            // Reflects current state
            this.state = State.getInstance();
            this.state.setConfig(this.config);

            this.poller = new Poller(this.getEventDispatcher());

            // Register listeners
            this.registerDefaultEventListeners();
        }
        setPaymentCompleteUrl(url)
        {
            this.config.payment_complete_url = this.urlHelper.ensureAbsoluteUrl(url);
        }


        /**
         * @returns {Object}
         */
        getConfig()
        {
            return this.config;
        }

        /**
         * @returns {Poller}
         */
        getPoller()
        {
            return this.poller;
        }

        /**
         * @returns {EventDispatcher}
         */
        getEventDispatcher()
        {
            return EventDispatcher.getInstance();
        }

        /**
         * Initialise the input fields and form.
         */
        init()
        {
            let form = this.getElementByReference(Events$1.form);

            if (true === this.config.html5_enhanced_validation) {
                this.applyAttributesToElementsOfForm(form);
            }

            this.createPayloadInput(form);
            this.bindings();
            this.triggerEventsWhenFormAlreadyHasInput();

            this.registerPaymentCompleteListener();
            this.registerErrorListener();
            this.debug('Initialization complete.');
        }

        /**
         * Set the bindings
         */
        bindings()
        {
            this.bindCreditCardHolder();
            this.bindCreditCardNumberInput();
            this.bindCreditCardCvcInput();
            this.bindCreditCardExpirationMonth();
            this.bindCreditCardExpirationYear();

            if (this.config.bind.submit) {
                this.bindForm();
            }
        }

        /**
         * Make sure the urls are set.
         */
        checkSettings()
        {
            let encryptedForm = this;
            let settingsToCheck = [
                'refresh_url',
                'status_url',
                'authentication_url',
                'authorization_url',
                'post_url',
                'payment_complete_url'
            ];

            settingsToCheck.forEach(function(key){
                if (null === encryptedForm.config[key]) {
                    throw new Error(`The ${key} config option must be defined.`);
                }
            });
        }

        /**
         * Handle the form submission
         *
         * @param element
         */
        handleFormSubmission(element)
        {
            let payload = {
                'browserJavaEnabled': navigator.javaEnabled(),
                'browserJavascriptEnabled': true,
                'browserLanguage': navigator.language,
                'browserColorDepth': screen.colorDepth,
                'browserScreenWidth': screen.width,
                'browserScreenHeight': screen.height,
                'browserTZ': new Date().getTimezoneOffset()
            };

            let cryptography = new OpenSSLEncrypter();
            let keyManager = new KeyManager(this.config.public_keys, this.config.refresh_url);
            let encryptedForm = this;

            const fieldSelector = this.config.field_selector;
            const fieldValueReader = this.config.field_value_reader;
            const merchantIdentifier = this.config.merchant_identifier;
            const language = this.config.language;

            encryptedForm.getEventDispatcher().dispatch(new StateChangeEvent(element, {
                'validation': null,
                'state': {loading: true, formSubmitted: true},
            }), Events.onStateChangeEvent);

            keyManager.getUnexpiredPublicKey().then((publicKey) => {
                element.querySelectorAll('[' + fieldSelector + ']').forEach(function (element) {
                    let field = element.getAttribute(fieldValueReader);
                    payload[field] = element.value;
                });

                let data = {
                    'identifier': publicKey.identifier,
                    'merchant_identifier': merchantIdentifier,
                    'language': language,
                    'payment_complete_url': encryptedForm.config.payment_complete_url,
                    'data': cryptography.encrypt(
                        publicKey.getKey(),
                        JSON.stringify(payload)
                    ).toString()
                };

                let formData = new FormData();
                formData.set('pay_encrypted_data', JSON.stringify(data));
                let postUrl = this.config.post_url;
                let formAction = null === postUrl ?
                    encryptedForm.urlHelper.ensureAbsoluteUrl(element.getAttribute('action')) :
                    postUrl;

                let submitDataEvent = new SubmitDataEvent(formData, {
                    'form_action': formAction,
                    'authorization_url': encryptedForm.config.authorization_url
                });

                EventDispatcher.getInstance().dispatch(submitDataEvent, Events.onSubmitDataEvent);
                EventDispatcher.getInstance().dispatch(new DebugEvent('Posting form data.', {
                    'url': submitDataEvent.getParameter('form_action'),
                    'data': submitDataEvent.getSubject()
                }), Events.onDebugEvent);

                fetch(submitDataEvent.getParameter('form_action'), {
                    'method': 'POST',
                    'cache': 'no-cache',
                    'redirect': 'follow',
                    'body': submitDataEvent.getSubject()
                })
                    .then((response, reject) => {
                        if (response.ok) {
                            EventDispatcher.getInstance().dispatch(new DebugEvent('Received RAW response.', {
                                'response': response
                            }), Events.onDebugEvent);

                            return response;
                        }


                        reject(Error('Unexpected response from server.'));
                    })
                    .then((response) => {
                        EventDispatcher.getInstance().dispatch(new DebugEvent('Converting RAW response to JSON.', {
                            'response': response
                        }), Events.onDebugEvent);

                        return response.json().catch(() => {
                            throw new Error('Invalid JSON returned.');
                        });
                    })
                    .then((json) => {
                        let event = new ResponseFactoryEvent(json);
                        EventDispatcher.getInstance().dispatch(event, Events.onResponseFactoryEvent);

                        let response = event.hasParameter('response') && event.getParameter('response') instanceof Response ?
                            event.getParameter('response') : null;

                        if (!response) {
                            throw new Error('Invalid response');
                        }

                        return response;
                    })
                    .then((response) => {
                        if (response instanceof ActionableResponse)
                        {
                            console.log('actie repose');
                            console.log(response);

                            encryptedForm.getEventDispatcher().dispatch(new ActionableResponseEvent(response, {
                                'encryptedForm': this,
                                'poller': this.poller,
                                'config': this.config
                            }), Events.onActionableResponseEvent);
                        } else {
                            throw new Error('Received non actionable response.');
                        }
                    })
                    .catch((e) => {
                        this.poller.clear();

                        encryptedForm.getEventDispatcher().dispatch(new StateChangeEvent(element, {
                            'validation': null,
                            'state': {loading: false, formSubmitted: false},
                            'exception': e
                        }), Events.onStateChangeEvent);

                        throw e;
                    });
            }).catch((e) => {
                this.poller.clear();

                encryptedForm.getEventDispatcher().dispatch(new StateChangeEvent(element, {
                    'validation': null,
                    'state': {loading: false, formSubmitted: false},
                    'exception': e
                }), Events.onStateChangeEvent);

                throw e;
            });
        }

        /**
         * Bind on the given form element, to hook into the on submit.
         */
        bindForm() {
            let form = this.getElementByReference(Events$1.form);
            let encryptedForm = this;
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                encryptedForm.handleFormSubmission(form);
            });
        }

        /**
         * Binding on the card holder.
         */
        bindCreditCardHolder()
        {
            let encryptedForm = this;

            document.addEventListener('input', function(e){
                let input = encryptedForm.getElementByReference(Events$1.creditCardHolder);

                if (e.target !== input) {
                    return;
                }

                let validator = cardValidator.cardholderName(input.value);
                let validation = encryptedForm.getValidationAttributeValue(validator);
                input.setAttribute('data-validation', validation);
                encryptedForm.getEventDispatcher().dispatch(new StateChangeEvent(input, {
                    'validation': input.getAttribute('data-validation'),
                    'state': {'cardHolderInputComplete': validation === 'valid'}
                }), Events.onStateChangeEvent);
            });
        }

        /**
         * Bind credit card number input
         */
        bindCreditCardNumberInput()
        {
            let encryptedForm = this;
            let type = 'default';

            document.addEventListener('input', function(e){
                let cardNumberInput = encryptedForm.getElementByReference(Events$1.creditCardNumber);

                if (e.target !== cardNumberInput) {
                    return;
                }

                let cvvInput = encryptedForm.getElementByReference(Events$1.creditCardCvv);
                let cvvLabel = encryptedForm.getElementByReference(Events$1.creditCardCvvLabel);

                let cardNumber = cardNumberInput.value;
                let validator = cardValidator.number(cardNumber);
                type = null !== validator.card && null !== validator.card.type ? validator.card.type : 'default';

                encryptedForm.renderCreditCardImage(
                    encryptedForm.getElementByReference(Events$1.creditCardImage),
                    type in encryptedForm.config.icons.creditcard ? type : 'default'
                );

                let cardLength = null !== validator.card ?
                    Math.max(...validator.card.lengths):
                    encryptedForm.config.default_card_number_length;
                let whitespacesInCard = (cardNumber.match(/\s/g) || []).length;

                let cvvLength = null !== validator.card ?
                    validator.card.code.size :
                    '4';

                let validation = encryptedForm.getValidationAttributeValue(validator);
                cardNumberInput.setAttribute('data-validation', validation);
                cardNumberInput.setAttribute('maxlength', (cardLength + whitespacesInCard).toString());
                cardNumberInput.setAttribute('minlength', cardLength);

                encryptedForm.getEventDispatcher().dispatch(new StateChangeEvent(cardNumberInput, {
                    'validation': cardNumberInput.getAttribute('data-validation'),
                    'state': {
                        cardNumberInputComplete: ( cardNumberInput.getAttribute('data-validation') === 'valid'
                            || cardNumberInput.getAttribute('data-validation') === 'potentially-valid')
                    }
                }), Events.onStateChangeEvent);

                cvvInput.setAttribute('minlength', cvvLength);
                cvvInput.setAttribute('maxlength', cvvLength);

                let cvvValidation = cvvInput.getAttribute('data-validation');
                encryptedForm.getEventDispatcher().dispatch(new StateChangeEvent(cvvInput, {
                    'validation': cvvValidation,
                    'state': {
                        'cardCvvInputComplete': 'valid' === cvvValidation && (validation === 'valid' || validation === 'potentially-valid')
                    }
                }), Events.onStateChangeEvent);

                cvvLabel.innerHTML = null !== validator.card ?
                    validator.card.code.name :
                    encryptedForm.config.default_cvv_label;
            });

            document.addEventListener('change', function(e){
                let cardNumberInput = encryptedForm.getElementByReference(Events$1.creditCardNumber);

                if (e.target !== cardNumberInput) {
                    return;
                }

                encryptedForm.getEventDispatcher().dispatch(new StateChangeEvent(cardNumberInput, {
                    'state': {
                        'challengeRetry': false
                    }
                }), Events.onStateChangeEvent);
            });

            new Cleave_1('input[name="cardnumber"]', {
                creditCard: true,
                creditCardStrictMode: true
            });

            this.renderCreditCardImage(
                encryptedForm.getElementByReference(Events$1.creditCardImage),
                type
            );
        }

        /**
         * Binding on the cvc input.
         */
        bindCreditCardCvcInput()
        {
            let encryptedForm = this;

            document.addEventListener('input', function(e){
                let cardNumberInput = encryptedForm.getElementByReference(Events$1.creditCardNumber);
                let cvvInput = encryptedForm.getElementByReference(Events$1.creditCardCvv);

                if (e.target !== cvvInput) {
                    return;
                }

                let cardValidation = cardValidator.number(cardNumberInput.value);
                let cvvValidation = null;

                if (null !== cardValidation.card && null !== cardValidation.card.code) {
                    cvvValidation = cardValidator.cvv(cvvInput.value, cardValidation.card.code.size);
                } else {
                    cvvValidation = cardValidator.cvv(cvvInput.value);
                }

                let validation = encryptedForm.getValidationAttributeValue(cvvValidation);
                cvvInput.setAttribute('data-validation', validation);
                encryptedForm.getEventDispatcher().dispatch(new StateChangeEvent(cvvInput, {
                    'validation': cvvInput.getAttribute('data-validation'),
                    'state': {'cardCvvInputComplete': (validation === 'valid' || validation === 'potentially-valid') }
                }), Events.onStateChangeEvent);
            });
        }

        /**
         * Binding on the expiration month.
         *
         * @todo single input for month + year support
         */
        bindCreditCardExpirationMonth()
        {
            let encryptedForm = this;

            document.addEventListener('change', function(e){
                let inputExpirationMonth = encryptedForm.getElementByReference(Events$1.creditCardExpirationMonth);
                let inputExpirationYear = encryptedForm.getElementByReference(Events$1.creditCardExpirationYear);

                if (e.target !== inputExpirationMonth) {
                    return;
                }

                encryptedForm.validateMonthYearCombination();
                encryptedForm.getEventDispatcher().dispatch(new StateChangeEvent(inputExpirationMonth, {
                    'validation': inputExpirationMonth.getAttribute('data-validation'),
                    'state': {
                        'cardExpiryMonthInputComplete': inputExpirationMonth.getAttribute('data-validation') === 'valid',
                        'cardExpiryYearInputComplete': inputExpirationYear.getAttribute('data-validation') === 'valid'
                    }
                }), Events.onStateChangeEvent);
            });
        }

        /**
         * Binding on the expiration year.
         *
         * @param element
         */
        bindCreditCardExpirationYear(element)
        {
            let encryptedForm = this;

            document.addEventListener('change', function(e){
                let inputExpirationMonth = encryptedForm.getElementByReference(Events$1.creditCardExpirationMonth);
                let inputExpirationYear = encryptedForm.getElementByReference(Events$1.creditCardExpirationYear);

                if (e.target !== inputExpirationYear) {
                    return;
                }

                encryptedForm.validateMonthYearCombination(element);
                encryptedForm.getEventDispatcher().dispatch(new StateChangeEvent(inputExpirationYear, {
                    'validation': inputExpirationYear.getAttribute('data-validation'),
                    'state': {
                        'cardExpiryMonthInputComplete': inputExpirationMonth.getAttribute('data-validation') === 'valid',
                        'cardExpiryYearInputComplete': inputExpirationYear.getAttribute('data-validation') === 'valid',
                    }
                }), Events.onStateChangeEvent);
            });
        }

        /**
         * Trigger events when form already has input ( month/year by default ), and demo input for example.
         *
         * This assures the State object gets proper information when the document is loaded.
         */
        triggerEventsWhenFormAlreadyHasInput()
        {
            let elements = [
                this.getElementByReference(Events$1.creditCardHolder),
                this.getElementByReference(Events$1.creditCardNumber),
                this.getElementByReference(Events$1.creditCardCvv),
                this.getElementByReference(Events$1.creditCardExpirationMonth),
                this.getElementByReference(Events$1.creditCardExpirationYear)
            ];

            elements.forEach(function(element){
                if (element.value) {
                    let eventType = null;

                    switch (element.nodeName.toLowerCase()) {
                        case 'select':
                            eventType = 'change';
                            break;
                        case 'input':
                            eventType = 'input';
                            break;
                    }

                    let event = new Event(eventType, {bubbles: true});
                    element.dispatchEvent(event);
                }
            });
        }

        /**
         * Validates the month & year combination for expiration.
         *
         * @returns {ExpirationDateVerification}
         */
        validateMonthYearCombination()
        {
            let monthInput = this.getElementByReference(Events$1.creditCardExpirationMonth);
            let yearInput = this.getElementByReference(Events$1.creditCardExpirationYear);

            let validator = cardValidator.expirationDate({
                'month': monthInput.value,
                'year': yearInput.value
            });

            monthInput.setAttribute('data-validation', this.getValidationAttributeValue(validator));
            yearInput.setAttribute('data-validation', this.getValidationAttributeValue(validator));

            return validator;
        }

        /**
         * After successful payment, this event is triggered on the last redirect page within the iframe.
         *
         * This way we can redirect the user again to the payment complete page, close the modal, raise a new model, etc.
         */
        registerPaymentCompleteListener() {
            let encryptedForm = this;
            window.addEventListener('payment-complete', function(triggerEvent) {

                let event = new PaymentCompleteEvent(triggerEvent, {
                    'encryptedForm': this,
                    'originalEvent': triggerEvent,
                    'paymentCompleteUrl': encryptedForm.config.payment_complete_url,
                    'urlParameters': triggerEvent.detail
                });

                EventDispatcher.getInstance().dispatch(
                    event,
                    Events.onPaymentCompleteEvent
                );
            });
        }

        /**
         * Within the context of 3ds payment in the iFrame we need a method to pass on errors that occurred during
         * the completion step of the payment.
         */
        registerErrorListener() {
            let encryptedForm = this;
            window.addEventListener('error', function(triggerEvent) {
                // fake as if this came from the API
                let errorResponse = new ErrorResponse(triggerEvent.detail);
                let event = new PaymentFailedEvent(errorResponse, {
                    'encryptedForm': this,
                    'originalEvent': triggerEvent,
                    'paymentCompleteUrl': encryptedForm.config.payment_complete_url,
                    'urlParameters': triggerEvent.detail
                });

                EventDispatcher.getInstance().dispatch(
                    event,
                    Events.onPaymentFailedEvent
                );
            });
        }

        /**
         * Apply attributes enhancement to all underlying form inputs that were registered.
         *
         * @param form
         */
        applyAttributesToElementsOfForm(form) {
            let fieldSelector = this.config.field_selector;
            let fieldValueReader = this.config.field_value_reader;

            form.querySelectorAll('[' + fieldSelector + ']').forEach(function (field) {
                let attributes = EncryptedForm.getAttributesForField(fieldValueReader, field);
                EncryptedForm.applyAttributesToField(field, attributes);
            });
        }

        /**
         * Apply given attributes to form element.
         *
         * @param field
         * @param attributes
         */
        static applyAttributesToField(field, attributes) {
            for (let [attribute, value] of Object.entries(attributes)) {
                if ('function' === typeof value) {
                    value = value(field);
                }

                if (null === value || field.hasAttribute(attribute)) {
                    continue;
                }

                field.setAttribute(attribute, value);
            }
        }

        /**
         * Retrieve attributes for given field and merge them with the defaults.
         *
         * @param fieldValueReader
         * @param field
         * @returns {any}
         */
        static getAttributesForField(fieldValueReader, field) {
            let fieldName = field.getAttribute(fieldValueReader);
            let attributes = EncryptedForm.getAttributes();

            if (undefined === attributes[fieldName]) {
                throw Error('Unknown field: ' + field);
            }

            return Object.assign({}, EncryptedForm.getDefaultAttributes(), attributes[fieldName]);
        }

        /**
         * Get validation value.
         *
         * @param validator
         * @returns {string|null}
         */
        getValidationAttributeValue(validator)
        {
            if (!validator.isValid && !validator.isPotentiallyValid) {
                return 'invalid';
            }

            if (validator.isValid && validator.isPotentiallyValid) {
                return 'valid';
            }

            if (validator.isPotentiallyValid) {
                return 'potentially-valid';
            }

            return null;
        }

        /**
         * Get element by reference.
         *
         * @param key
         * @return {*}
         */
        getElementByReference(key) {
            try {
                return this.state.getElementFromReference(key);
            } catch (e) {
                return null;
            }
        }

        /**
         * Return attributes for field elements.
         *
         * @returns {{card_holder: {}, cvc: {minlength: (function(*): number|null), maxlength: (function(*): number|null), pattern: cvc.pattern, title: string}, expiry_month: {pattern: string, title: string}, expiry: {pattern: string, title: string}, pan: {minlength: (function(*): number|null), maxlength: (function(*): number|null), pattern: pan.pattern, title: string}, expiry_year: {pattern: string, title: string}}}
         */
        static getAttributes() {
            return {
                'cardholder': {},
                'cardnumber': {
                    'type': 'text',
                    'minlength': function (field) {
                        return 'number' === field.getAttribute('type') ? 13 : null;
                    },
                    'maxlength': function (field) {
                        return 'number' === field.getAttribute('type') ? 16 : null;
                    },
                    'inputmode': 'numeric',
                    'autocomplete': 'cc-number',
                    'autocorrect': 'off'
                },
                'cardcvc': {
                    'minlength': function (field) {
                        return 'number' === field.getAttribute('type') ? 3 : null;
                    },
                    'maxlength': function (field) {
                        return 'number' === field.getAttribute('type') ? 4 : null;
                    },
                    'pattern': function (field) {
                        if ('text' === field.getAttribute('type') || null === field.getAttribute('type')) {
                            return '[0-9]{3,4}';
                        }

                        return null;
                    },
                    'inputmode': 'numeric',
                    'autocomplete': 'cc-csc',
                    'autocorrect': 'off'
                },
                'expiry': {
                    'pattern': '(20|21)[0-9]{2}-[0-9]{2}',
                    'inputmode': 'numeric',
                    'autocomplete': 'cc-exp',
                    'autocorrect': 'off'
                },
                'valid_thru_month': {
                    'pattern': '(0[1-9]|1[012])',
                    'inputmode': 'numeric',
                    'autocomplete': 'cc-exp-month',
                    'autocorrect': 'off'
                },
                'valid_thru_year': {
                    'pattern': '(20|21)[0-9]{2}',
                    'inputmode': 'numeric',
                    'autocomplete': 'cc-exp-year',
                    'autocorrect': 'off'
                }
            };
        }

        /**
         * Return default attributes that apply to all data.
         *
         * @returns {{required: string}}
         */
        static getDefaultAttributes() {
            return {
                'required': 'required'
            };
        }

        /**
         * Create the hidden payload input where our encrypted data will reside prior to submitting the form.
         *
         * @param element
         * @returns {*}
         */
        createPayloadInput(element) {
            let input = document.createElement('input');

            input.setAttribute('type', 'hidden');
            input.setAttribute('name', this.config.form_input_payload_name);
            element.appendChild(input);

            return element;
        }

        /**
         * Create a hidden container for when we have to handle the TDSMethod.
         */
        createTdsMethodPlaceHolder() {
            let container = document.createElement('div');

            container.setAttribute('style', 'display:none;height:0px;width:0px;');
            container.setAttribute('id', Events$1.tdsMethodPlaceholderId);
            document.querySelector('body').appendChild(container);
        }

        /**
         * Helper to render the CreditCard image.
         *
         * @param element
         * @param type
         */
        renderCreditCardImage(element, type)
        {
            if (null === element || element.getAttribute('data-cc-type') === type) {
                return;
            }

            element.src = this.config.icons.creditcard[type];
            element.setAttribute('data-cc-type', type);
        }

        /**
         * Debug helper
         *
         * @param subject
         * @param parameters
         */
        debug(subject, parameters = {}) {
            if (false === this.config.debug) {
                return;
            }

            this.getEventDispatcher().dispatch(
                new DebugEvent(subject, parameters),
                Events.onDebugEvent
            );
        }

        /**
         * Register default event listeners
         *
         * By default we set our own priority quite low, so it allows for extension.
         * If you want the default listeners not triggered for some events, simply call event.stopPropagation().
         *
         * @return <void>
         */
        registerDefaultEventListeners()
        {
            if (this.config.debug) {
                this.registerDebugListeners();
            } else {
                this.getEventDispatcher().addListener(Events.onDebugEvent, event => DebugListener.onDebugNull(event),50);
            }

            let pollingListener = new PollingListener(this.poller);

            this.getEventDispatcher().addListener(Events.onPaymentRequiresChallengeEvent, event => PaymentListener.onPaymentRequiresChallengeEvent(event), 50);
            this.getEventDispatcher().addListener(Events.onPaymentCanceledEvent, event => PaymentListener.onPaymentCanceledEvent(event), 50);
            this.getEventDispatcher().addListener(Events.onPaymentCompleteEvent, event => PaymentListener.onPaymentCompleteEvent(event), 50);
            this.getEventDispatcher().addListener(Events.onPaymentCompleteEvent, event => StateChangeListener.onPaymentCompleteEvent(event), 60);
            this.getEventDispatcher().addListener(Events.onPaymentFailedEvent, event => StateChangeListener.onPaymentFailedEvent(event), 60);
            this.getEventDispatcher().addListener(Events.onPaymentFailedEvent, event => PaymentListener.onPaymentFailedEvent(event), 50);
            this.getEventDispatcher().addListener(Events.onResponseEvent, event => ResponseToJsonListener.onResponseEvent(event), 50);
            this.getEventDispatcher().addListener(Events.onResponseFactoryEvent, event => ResponseFactoryListener.onResponseEvent(event), 50);
            this.getEventDispatcher().addListener(Events.onResponseFactoryEvent, event => StateChangeListener.onResponseFactoryEvent(event), 70);
            this.getEventDispatcher().addListener(Events.onStateChangeEvent, event => StateChangeListener.onStateChangeEvent(event), 50);
            this.getEventDispatcher().addListener(Events.onStateChangeEvent, event => FormSubmissionListener.onStateChangeEvent(event), 50);
            this.getEventDispatcher().addListener(Events.onStateChangeEvent, event => FormDisableElementsListener.onStateChangeEvent(event), 50);
            this.getEventDispatcher().addListener(Events.onModalOpenEvent, event => ModalListener.onModalOpen(event), 50);
            this.getEventDispatcher().addListener(Events.onModalCloseEvent, event => ModalListener.onModalClose(event), 50);
            this.getEventDispatcher().addListener(Events.onModalCloseEvent, event => pollingListener.onStopPolling(event), 50);
            this.getEventDispatcher().addListener(Events.onResolveModalEvent, event => ResolveModalListener.onResolveModal(event), 50);
            this.getEventDispatcher().addListener(Events.onPollingResponseEvent, event => pollingListener.onPollingResponse(event), 50);
            this.getEventDispatcher().addListener(Events.onPaymentAuthorizeEvent, event => PaymentListener.onPaymentAuthorizeEvent(event), 50);
            this.getEventDispatcher().addListener(Events.onActionableResponseEvent, event => ActionableResponseListener.onActionableResponse(event), 50);
            this.getEventDispatcher().addListener(Events.onPaymentRequiresTdsMethodEvent, event => PaymentListener.onPaymentRequiresTdsMethodEvent(event), 50);
            this.getEventDispatcher().addListener(Events.onPaymentAuthenticateEvent, event => PaymentListener.onPaymentAuthenticateEvent(event), 50);
            this.getEventDispatcher().addListener(Events.onSubmitDataEvent, event => PaymentListener.onSubmitDataEvent(event), 50);
            this.getEventDispatcher().addListener(Events.onSubmitDataEvent, event => StateChangeListener.onSubmitDataEvent(event), 60);
        }

        /**
         * Register debugging listeners.
         *
         * @return <void>
         */
        registerDebugListeners()
        {
            this.getEventDispatcher().addListener(Events.onDebugEvent, event => DebugListener.onDebugEvent(event),50);
            this.getEventDispatcher().addListener(Events.onModalCloseEvent, event => DebugListener.onModalCloseEvent(event),50);
            this.getEventDispatcher().addListener(Events.onModalOpenEvent, event => DebugListener.onModalOpenEvent(event),50);
            this.getEventDispatcher().addListener(Events.onPaymentCompleteEvent, event => DebugListener.onPaymentCompleteEvent(event),50);
            this.getEventDispatcher().addListener(Events.onPaymentFailedEvent, event => DebugListener.onPaymentFailedEvent(event),50);
            this.getEventDispatcher().addListener(Events.onResponseFactoryEvent, event => DebugListener.onResponseFactoryEvent(event),50);
            this.getEventDispatcher().addListener(Events.onRequestEvent, event => DebugListener.onRequestEvent(event),50);
            this.getEventDispatcher().addListener(Events.onResponseEvent, event => DebugListener.onResponseEvent(event),50);
            this.getEventDispatcher().addListener(Events.onStateChangeEvent, event => DebugListener.onStateChangeEvent(event),50);
        }
    }

    exports.ActionableResponseEvent = ActionableResponseEvent;
    exports.AuthorizingModal = AuthorizingModal;
    exports.ChallengeModal = ChallengeModal;
    exports.DebugEvent = DebugEvent;
    exports.ElementReferences = ElementReferences;
    exports.Elements = Events$1;
    exports.EncryptedForm = EncryptedForm;
    exports.ErrorModal = ErrorModal;
    exports.EventDispatcher = EventDispatcher;
    exports.EventListener = EventListener;
    exports.Events = Events;
    exports.FormDisableElementsListener = FormDisableElementsListener;
    exports.FormSubmissionListener = FormSubmissionListener;
    exports.GenericEvent = GenericEvent;
    exports.ModalCloseEvent = ModalCloseEvent;
    exports.ModalListener = ModalListener;
    exports.ModalOpenEvent = ModalOpenEvent;
    exports.PaymentAuthorizationEvent = PaymentAuthorizationEvent;
    exports.PaymentCanceledEvent = PaymentCanceledEvent;
    exports.PaymentCompleteEvent = PaymentCompleteEvent;
    exports.PaymentCompleteModal = PaymentCompleteModal;
    exports.PaymentFailedEvent = PaymentFailedEvent;
    exports.PaymentListener = PaymentListener;
    exports.PaymentRequiresChallengeEvent = PaymentRequiresChallengeEvent;
    exports.PaymentRequiresTdsMethodEvent = PaymentRequiresTdsMethodEvent;
    exports.PollingListener = PollingListener;
    exports.PollingResponse = PollingResponse;
    exports.PollingResponseEvent = PollingResponseEvent;
    exports.ResolveModalListener = ResolveModalListener;
    exports.ResponseFactoryEvent = ResponseFactoryEvent;
    exports.ResponseFactoryListener = ResponseFactoryListener;
    exports.ResponseToJsonListener = ResponseToJsonListener;
    exports.State = State;
    exports.StateChangeEvent = StateChangeEvent;
    exports.StateChangeListener = StateChangeListener;
    exports.ThreeDSTransactionStatus = ThreeDSTransactionStatus;
    exports.UrlHelper = UrlHelper;

    Object.defineProperty(exports, '__esModule', { value: true });

}));
