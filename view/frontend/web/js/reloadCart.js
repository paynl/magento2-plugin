define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($,customerData) {
    'use strict';

    return function (config, element) {
        var sections = ['cart'];
        customerData.invalidate(sections);
        customerData.reload(sections, true);
    }
});