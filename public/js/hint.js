/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

(function (Icinga) {

    'use strict';

    let $ = window.$;

    // try {
    //     $ = require('icinga/icinga-php-library/notjQuery');
    // } catch (e) {
    //     console.warn('[Hint] notjQuery unavailable. Using jQuery for now');
    // }

    Icinga.Behaviors = Icinga.Behaviors || {};

    /**
     * Behavior for hint popups.
     *
     * @param  icinga  Icinga  The current Icinga Object
     */
    class Hint extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            this.on('rendered', '#main > .container', this.onRendered, this);
            this.on('click', '.reorder-hint button.close-forever', this.onReorderHintCloseForever, this);
            this.on('click', '.reorder-hint button.close', this.onReorderHintMinimize, this);
            this.on('click', '.reorder-hint.minimize', this.onReorderHintMaximize, this);

            this.$popups = [];

            this.state = Icinga.Storage.BehaviorStorage('hint');
            this.state.setBackend(window.localStorage);
        }

        onRendered(event, isAutoRefresh) {
            let _this = event.data.self;
            let $container = $(event.target);
            let hint = $container.find('.content').data('hint');
            let id = $container.find('.content').data('hintId');

            if (! isAutoRefresh && hint !== undefined && ! _this.state.get(`reorder-hint-never-show-again-${ id }`)) {
                _this.showPopup($container, hint, id);

                if (_this.state.get(`reorder-hint-minimize-${ id }`)) {
                    _this.minimizePopup(id);
                }

                let $content = $container.find('.content');
                let $reorderHint = $('#reorder-hint');
                $content.css('padding-bottom',
                    $reorderHint.height() +
                    parseFloat($reorderHint.css('padding')) * 2
                    + parseFloat($content.css('padding-bottom')) * 2
                );

                _this.state.remove(`reorder-hint-show-${ id }`)
            }
        }

        showPopup($container, hint, id) {
            this.popup($container, hint, id);
        }

        hidePopup(id) {
            this.popup(null, null, id).addClass('hide');
        }

        minimizePopup(id) {
            this.popup(null, null, id).addClass('minimize');
        }

        maximizePopup(id) {
            this.popup(null, null, id).removeClass('minimize');
        }

        popup($container, hint, id) {
            console.log(this.$popups[id]);
            if (this.$popups[id] === undefined || ! document.body.contains(this.$popups[id][0])) {
                $container.css('position', 'relative');
                $container.append(this.popupHtml(hint, id));
                this.$popups[id] = $(`#reorder-hint-${ id }`);
            }

            return this.$popups[id];
        }

        onReorderHintCloseForever(event) {
            let _this = event.data.self;
            let $container = $(event.target.closest('.container'));
            let id = $container.find('.content').data('hintId');
            _this.state.set(`reorder-hint-never-show-again-${ id }`, true);
            _this.hidePopup(id);
        }

        onReorderHintMinimize(event) {
            let _this = event.data.self;
            let $container = $(event.target.closest('.container'));
            let id = $container.find('.content').data('hintId');
            _this.state.set(`reorder-hint-minimize-${ id }`, true)
            _this.minimizePopup(id)
        }

        onReorderHintMaximize(event) {
            let _this = event.data.self;
            let $container = $(event.target.closest('.container'));
            let id = $container.find('.content').data('hintId');
            _this.state.set(`reorder-hint-minimize-${ id }`, false)
            _this.maximizePopup(id)
        }

        onFavoriteToggleChanged(event) {
            let _this = event.data.self;
            if (event.originalEvent.target.checked && ! _this.state.get('reorder-hint-never-show-again')) {
                event.data.self.state.set(`reorder-hint-show-${ id }`, true);
            }
        }

        popupHtml(hint, id) {
            return `<div class="reorder-hint" id="reorder-hint-${ id }">\n` +
                `   <i class="icon fa-lightbulb fa"></i>\n` +
                `   <p>${ hint }</p>\n` +
                `   <span class="button-container">\n` +
                `      <button class="close link-button">Close</button>\n` +
                `      <button class="close-forever link-button">Never show again</button>\n` +
                `   </span>\n` +
                `   <i class="minimize fa-minus fa"></i>\n` +
                `</div>`;
        }
    }

    Icinga.Behaviors.Hint = Hint;

})(Icinga);
