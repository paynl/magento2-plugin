define([], function () {
    'use strict';

    return function (config) {
        window.fastCheckoutMinicart = config.minicartEnabled;
        window.fastCheckoutModalEnabled = config.modalEnabled;
    };
});