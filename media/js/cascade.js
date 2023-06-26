/*
 * @package   pkg_radicalreviews
 * @version   __DEPLOY_VERSION__
 * @author    Dmitriy Vasyukov - https://fictionlabs.ru
 * @copyright Copyright (c) 2023 Fictionlabs. All rights reserved.
 * @license   GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link      https://fictionlabs.ru/
 */

"use strict";

class RadicalMartFieldsCascade {
    constructor(container) {
        this.container = container;
        // this.subform = this.container.closest('joomla-field-subform');
        this.id = this.container.getAttribute('data-id');
        this.options = null;
        this.items = Joomla.getOptions('plg_radicalmart_fields_cascade_' + this.id);
        this.not_use = Joomla.Text._('JOPTION_DO_NOT_USE');
    }

    initialize() {
        this.initRepeatable();
        this.initSelects();

        let $this = this;

        document.querySelectorAll('select').forEach((select, i) => {
            select.addEventListener('change', function (event) {
                let selectIndex = parseInt(select.getAttribute('data-index')),
                    selectValue = select.value,
                    parentValues = $this.getParentValues(selectIndex),
                    selectNext = $this.container.querySelector('[data-index="' + (selectIndex + 1) + '"]');

                // if (selectNext.querySelectorAll('option').length === 2) {
                //     selectNext.value(selectNext.querySelector('option:nth-child(2)').value);
                // }

                if (selectNext) {
                    $this.fillSelect(selectNext, selectValue, parentValues, false);
                    selectNext.dispatchEvent(new Event('change'));
                }
            });
        });
    }

    initSelects() {
        let $this = this,
            selects = this.container.querySelectorAll('select');


        selects.forEach((select, i) => {
            let selectClass = new RadicalMartFieldSelect(select);
            let value = select.value;

            selectClass.disable();

            if (!$this.checkValue(value)) {
                selectClass.enable();
            }

            if (select.querySelectorAll('option').length > 1) {
                selectClass.enable();
            } else {
                selectClass.replaceOptions($this.convertOptions({}, this.getName(select)));
                selectClass.disable();
            }
        });
    }

    initRepeatable() {
        let $this = this;

        document.addEventListener('subform-row-add', function (event) {
            let row = event.detail.row,
                newCascadeContainer = row.querySelector('[data-cascade="container"]');

            newCascadeContainer.setAttribute('data-id', $this.id);

            row.querySelectorAll('select').forEach((select, i) => {
                let selectClass = new RadicalMartFieldSelect(select);

                selectClass.replaceOptions( {'': $this.text_all});
                selectClass.disable();

                if (i === 0) {
                    selectClass.replaceOptions($this.convertOptions($this.items, $this.getName(select)));
                    selectClass.enable();
                }
            });

            new RadicalMartFieldsCascade(newCascadeContainer).initialize();
        });
    }

    getParentValues(selectIndex) {
        let result = {};

        for (var i = 0; i <= selectIndex; i++) {
            result[i] = this.container.querySelector('[data-index="' + i + '"]').value;
        }

        return result;
    }

    fillSelect(select, value, parentValues, force) {
        // console.info(select);
        // console.info(value);
        // console.info(parentValues);

        let $this = this,
            tempList = this.items,
            selectClass = new RadicalMartFieldSelect(select);

        if (!force) {
            for (const [key, value] of Object.entries(parentValues)) {
                // console.info(tempList[value]);
                if (typeof tempList[value] != 'undefined') {
                    tempList = tempList[value];

                } else {
                    tempList = {};
                    // return false;
                }
            }
        }

        let newList = this.convertOptions(tempList, this.getName(select));

        selectClass.replaceOptions(newList);

        if (select.querySelectorAll('option').length > 1) {
            selectClass.enable();
        } else {
            selectClass.disable();
        }
    }

    checkValue(value) {
        if (typeof value == 'undefined') {
            return false;
        }

        return !['', ' ', '0'].includes(value);
    }

    convertOptions(items, firstOption = false) {
        let result = {};

        if (firstOption) {
            result = {'': Object.keys(items).length ? firstOption : this.not_use};
        }

        for (const [key, value] of Object.entries(items)) {
            result[key] = key;
        }

        return result;
    }

    getName(select) {
        return '- ' + select.getAttribute('data-name') + ' -';
    }

    setVariable(key, value) {
        this[key] = value;
    }

    getVariable(key, defaultValue = null) {
        return (!this[key] || this[key] === null) ? defaultValue : this[key];
    }
}

class RadicalMartFieldSelect {
    constructor(select) {
        this.select = select;
    }

    toggle(isEnabled) {
        if (isEnabled) {
            this.select.removeAttribute('disabled');
            this.select.closest('[data-cascade="parent"]').style.display = '';
        } else {
            this.select.setAttribute('disabled', 'disabled');
            this.select.closest('[data-cascade="parent"]').style.display = 'none';
        }

        // this.update();
    }

    disable() {
        this.toggle(false);
    }

    enable() {
        this.toggle(true);
    }

    removeOptions(notFirst = false, isUpdate) {
        if (notFirst) {
            // this.select.querySelector('option:not(:first-child)').remove();
            this.select.querySelectorAll('option:not(:first-child)').forEach((item, key) => {
                item.remove();
            });
        } else {
            if (this.select.querySelectorAll('option').length) {
                this.select.querySelectorAll('option').forEach((item, key) => {
                    item.remove();
                });
            }
        }

        // if (this.def(isUpdate, true)) {
        //     this.update();
        // }
    }

    replaceOptions(newOptions) {
        this.removeOptions();
        this.newOptions(newOptions);
    }

    newOptions(list, notFirst) {
        this.removeOptions(notFirst, false);
        this.addOptions(list, false);

        // this._update();
    }

    addOptions(list, isUpdate) {
        // isUpdate = this.def(isUpdate, true);

        for (const [key, value] of Object.entries(list)) {
            this.addOption(key, value);
        }

        // if (isUpdate) {
        //     this.update();
        // }
    }

    addOption(key, value) {
        let option = document.createElement('option');
        option.value = key;
        option.innerHTML = value;

        this.select.appendChild(option);
    }
}

// Init cascade select
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-cascade="container"]').forEach((container, i) => {
        new RadicalMartFieldsCascade(container).initialize();
    });
});