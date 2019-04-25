define([
        "jquery"
    ],
    function ($) {

        'use strict';

        var selectorEventHandlers = [];

        return {

            init: function () {
                $(document).ready(function () {
                    this.setLabelDependingOnSelect({
                        selector: 'price_vs_dest',
                        labelFor: 'weight_price',
                        values: {
                            '0': WEIGHT_AND_ABOVE_LABEL,
                            '1': PRICE_AND_ABOVE_LABEL
                        },
                        notes: {
                            '0': WEIGHT_AND_ABOVE_NOTE,
                            '1': PRICE_AND_ABOVE_NOTE
                        },
                        isRequired: true,
                        validation: ['validate-number']
                    });

                    this.setLabelDependingOnSelect({
                        selector: 'markup_type',
                        labelFor: 'price',
                        values: {
                            '0': SHIPPING_PRICE_LABEL,
                            '1': SHIPPING_PERCENTAGE_LABEL,
                            '2': SHIPPING_FIXED_AMOUNT_LABEL
                        },
                        isRequired: true,
                        validation: ['validate-number']
                    });

                    this.setLabelDependingOnSelect({
                        selector: 'cod_option',
                        labelFor: 'cashondelivery_surcharge',
                        values: {
                            '0': null,
                            '1': null,
                            '2': COD_SURCHARGE_FIXED_LABEL,
                            '3': COD_SURCHARGE_PERCENTAGE_LABEL
                        },
                        isRequired: true,
                        validation: ['validate-number']
                    });

                    this.setLabelDependingOnSelect({
                        selector: 'cod_option',
                        labelFor: 'cod_min_surcharge',
                        values: {
                            '0': null,
                            '1': null,
                            '2': null,
                            '3': COD_MIN_SURCHARGE_LABEL
                        },
                        isRequired: false,
                        validation: ['validate-number']
                    });

                    this.hideFieldsDependingOnSelect('shipping_method_enabled',
                        ['0'],
                        [{'id': 'markup_type', 'isRequired': true, validation: []},
                            {
                                'id': 'price',
                                'isRequired': true,
                                validation: ['validate-number'],
                                'onShow': this.blankPricePercentage
                            },
                            {'id': 'cod_option', 'isRequired': true, validation: []},
                            {'id': 'cashondelivery_surcharge', 'isRequired': true, validation: ['validate-number']},
                            {'id': 'cod_min_surcharge', 'isRequired': false, validation: ['validate-number']}

                        ]
                    );

                    $('button#duplicate').click(function () {
                        var form = $('#edit_form');
                        var tablerateId = $('#tablerate_id').val();

                        if (tablerateId && form.length) {
                            form[0].action += 'duplicate/1';
                            form.submit();
                        }
                    });

                    $('button#delete').click(function () {
                        var form = $('#edit_form');

                        if (form.length) {
                            form[0].action = form[0].action.replace('/save/', '/delete/');
                            form.submit();
                        }
                    });

                }.bind(this));
            },

            blankPricePercentage: function () {
                var value = $('#price').val();
                if (!isNaN(parseFloat(value)) && isFinite(value) && value < 0) {
                    $('#price').val('');
                }
            },

            setLabelDependingOnSelect: function (options) {

                var element = $('#' + options.labelFor),
                    elementRow = $(this.getElementRow(options.labelFor)),
                    label = $(this.getLabelForId(options.labelFor)),
                    select = $('#' + options.selector),
                    note = $(this.getNoteForId(options.labelFor)),
                    labelText = null,
                    handler = null;

                if (!element || !label || !select) {
                    return false;
                }

                handler = function () {

                    var i = 0,
                        validatorsCount = options.validation.length;

                    labelText = options.values[select.val()];
                    if (labelText) {
                        if (options.isRequired) {
                            labelText += ' <span class="required">*</span>';
                            element.addClass('required-entry');
                        }
                        for (i = 0; i < validatorsCount; i += 1) {
                            element.addClass(options.validation[i]);
                        }
                        label.html(labelText);

                        if (note && options.notes && options.notes[select.val()]) {
                            note.html(options.notes[select.val()]);
                        }
                        if (elementRow) {
                            elementRow.show();
                        }
                        element.show();
                        label.show();
                    } else {
                        if (elementRow) {
                            elementRow.hide();
                        }
                        element.hide();
                        $(label).hide();
                        element.removeClass('required-entry');
                        for (i = 0; i < validatorsCount; i += 1) {
                            element.removeClass(options.validation[i]);
                        }
                        element.val('');
                    }

                };

                selectorEventHandlers.push(handler);

                handler();

                select.on('change', handler);

                return true;
            },

            hideFieldsDependingOnSelect: function (selectId, selectHideValues, hiddenFields) {
                var select = $(selectId),
                    that = this,
                    handler = null;

                if (!select) {
                    return false;
                }


                handler = function () {
                    var i = 0,
                        j = 0,
                        hiddenFieldsLength = hiddenFields.length,
                        hide = false,
                        field = null,
                        fieldRow = null,
                        validatorsCount = null;

                    hide = selectHideValues.indexOf(select.val()) >= 0;
                    for (i = 0; i < hiddenFieldsLength; i += 1) {
                        field = $(hiddenFields[i].id);
                        if (!field) {
                            continue;
                        }

                        fieldRow = that.getElementRow(hiddenFields[i].id);
                        if (!fieldRow) {
                            continue;
                        }

                        if (hide) {
                            fieldRow.hide();
                            field.removeClass('required-entry');
                            for (j = 0, validatorsCount = hiddenFields[i].validation.length; j < validatorsCount; j += 1) {
                                field.removeClass(hiddenFields[i].validation[j]);
                            }
                        } else {
                            fieldRow.show();
                            if (hiddenFields[i].isRequired) {
                                field.addClass('required-entry');
                            }
                            for (j = 0, validatorsCount = hiddenFields[i].validation.length; j < validatorsCount; j += 1) {
                                field.addClass(hiddenFields[i].validation[j]);
                            }
                            if (hiddenFields[i].onShow) {
                                hiddenFields[i].onShow();
                            }
                            that.executeSelectorEventHandlers();
                        }
                    }

                };


                handler();

                select.on('change', handler);

                return true;

            },

            executeSelectorEventHandlers: function () {
                var i = 0,
                    count = selectorEventHandlers.length;
                for (i = 0; i < count; i += 1) {
                    selectorEventHandlers[i]();
                }
            },

            getLabelForId: function (id) {
                var labels = $('label[for="' + id + '"]');

                if (labels.length > 0) {
                    return labels[0];
                } else {
                    return false;
                }
            },

            getElementRow: function (id) {
                var elemId = '#' + id;
                var elem = $(elemId);

                if (elem.length) {
                    return elem.parents('div.field');
                } else {
                    return false;
                }
            },

            getNoteForId: function (id) {
                var row = this.getElementRow(id);
                if (!row) {
                    return false;
                }
                var note = row.find('div.note');
                return note.length ? note : false;
            }
        }
    }
);
