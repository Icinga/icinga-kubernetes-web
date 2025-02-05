/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

(function (Icinga) {

    "use strict";

    try {
        var Sortable = require('icinga/icinga-php-library/vendor/Sortable');
    } catch (e) {
        console.warn('Unable to provide Drag&Drop in the favorite lists. Libraries not available:', e);
        return;
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    class ActionList extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            this.on('click', '.action-list-kubernetes [data-action-item]:not(.page-separator), .action-list-kubernetes [data-action-item] a[href]', this.onClick, this);
            this.on('close-column', '#main > #col2.module-kubernetes', this.onColumnClose, this);
            this.on('column-moved', this.onColumnMoved, this);

            this.on('rendered', '#main .container.module-kubernetes:has(.action-list-kubernetes)', this.onRendered, this);
            this.on('keydown', '#body', this.onKeyDown, this);

            this.on('rendered', '#main .container.module-kubernetes:has([favorite-list])', this.onRenderedReorder, this);
            this.on('end', '[favorite-list]', this.onDropReorder, this)

            this.lastActivatedItemUrl = null;
            this.lastTimeoutId = null;
            this.activeRequests = {};
        }

        /**
         * Parse the filter query contained in the given URL query string
         *
         * @param {string} queryString
         *
         * @returns {array}
         */
        parseSelectionQuery(queryString) {
            return queryString.split('|');
        }

        /**
         * Suspend auto refresh for the given item's container
         *
         * @param {Element} item
         *
         * @return {string} The container's id
         */
        suspendAutoRefresh(item) {
            const container = item.closest('.container');
            container.dataset.suspendAutorefresh = '';

            return container.id;
        }

        /**
         * Enable auto refresh on the given container
         *
         * @param {string} containerId
         */
        enableAutoRefresh(containerId) {
            delete document.getElementById(containerId).dataset.suspendAutorefresh;
        }

        onClick(event) {
            let _this = event.data.self;
            let target = event.currentTarget;

            if (event.target.matches('.favorite-checkbox') || event.target.matches('.favorite-checkbox-label')) {
                return true
            }

            if (target.matches('a') && (! target.matches('.subject') || event.ctrlKey || event.metaKey)) {
                return true;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            event.stopPropagation();

            let item = target.closest('[data-action-item]');
            let list = target.closest('.action-list-kubernetes');
            let groupLists = Array.from(document.querySelectorAll(`.action-list-kubernetes[data-list-group=${ list.getAttribute('data-list-group') }]`)).filter(item => item !== list);
            let activeItems = _this.getActiveItems(list);
            let toActiveItems = [item];
            let toDeactivateItems = activeItems;

            for (let groupList of groupLists) {
                toDeactivateItems.push(..._this.getActiveItems(groupList));

                let groupListItem = groupList.querySelector(`[data-action-item][data-icinga-detail-filter='${ item.getAttribute('data-icinga-detail-filter') }']`);
                if (groupListItem !== null) {
                    toActiveItems.push(groupListItem);
                }
            }

            if (activeItems.length === 1
                && toActiveItems.length === 0
                && _this.icinga.loader.getLinkTargetFor($(target)).attr('id') === 'col2'
            ) {
                _this.icinga.ui.layout1col();
                _this.icinga.history.pushCurrentState();
                _this.enableAutoRefresh('col1');
                return;
            }

            let dashboard = list.closest('.dashboard');
            if (dashboard) {
                _this.clearDashboardSelections(dashboard, list);
            }

            let lastActivatedUrl = null;
            if (toActiveItems.includes(item)) {
                lastActivatedUrl = item.dataset.icingaDetailFilter;
            } else if (activeItems.length > 1) {
                lastActivatedUrl = activeItems[activeItems.length - 1] === item
                    ? activeItems[activeItems.length - 2].dataset.icingaDetailFilter
                    : activeItems[activeItems.length - 1].dataset.icingaDetailFilter;
            }

            sessionStorage.setItem('active-column', _this.icinga.utils.getCSSPath(list.parentElement.parentElement));
            sessionStorage.setItem('active-list', _this.icinga.utils.getCSSPath(list));

            _this.clearSelection(toDeactivateItems);
            _this.setActive(toActiveItems);
            _this.setLastActivatedItemUrl(lastActivatedUrl);
            _this.loadDetailUrl(list, target.matches('a') ? target.getAttribute('href') : null);
        }

        /**
         * Key navigation for .action-list-kubernetes
         *
         * Only for primary lists (dashboard or lists in detail view are not taken into account)
         *
         * - `ArrowUp|ArrowDown` = Select next/previous
         * - `Ctrl|cmd + A` = Select all on currect page
         *
         * @param event
         */
        onKeyDown(event) {
            let _this = event.data.self;
            let list = null;
            let pressedArrowDownKey = event.key === 'ArrowDown';
            let pressedArrowUpKey = event.key === 'ArrowUp';
            let focusedElement = document.activeElement;

            if (
                ! event.key // input auto-completion is triggered
                || (event.key.toLowerCase() !== 'a' && ! pressedArrowDownKey && ! pressedArrowUpKey)
            ) {
                return;
            }

            list = document.querySelector(sessionStorage.getItem('active-list'));

            if (! list) {
                if (focusedElement && (
                    focusedElement.matches('#main > :scope')
                    || focusedElement.matches('#body'))
                ) {
                    let activeItem = document.querySelector(
                        '#main > .container > .content > .action-list-kubernetes [data-action-item].active'
                    );
                    if (activeItem) {
                        list = activeItem.closest('.action-list-kubernetes');
                    } else {
                        list = focusedElement.querySelector('#main > .container > .content > .action-list-kubernetes');
                    }
                } else if (focusedElement) {
                    list = focusedElement.closest('.content > .action-list-kubernetes');
                }
            }

            if (! list) {
                return;
            }

            let groupLists = Array.from(document.querySelectorAll(`.action-list-kubernetes[data-list-group=${ list.getAttribute('data-list-group') }]`)).filter(item => item !== list);

            event.preventDefault();

            let allItems = _this.getAllItems(list);
            let firstListItem = allItems[0];
            let lastListItem = allItems[allItems.length - 1];
            let activeItems = _this.getActiveItems(list);
            let markAsLastActive = null; // initialized only if it is different from toActiveItem
            let toActiveItem = null;
            let toActiveItems = [];
            let toDeactivateItems = [];
            let wasAllSelected = activeItems.length === allItems.length;
            let lastActivatedItem = list.querySelector(
                `[data-icinga-detail-filter="${ _this.lastActivatedItemUrl }"]`
            );

            if (! lastActivatedItem && activeItems.length) {
                lastActivatedItem = activeItems[activeItems.length - 1];
            }

            let directionalNextItem = _this.getDirectionalNext(lastActivatedItem, event.key);

            if (activeItems.length === 0) {
                toActiveItem = pressedArrowDownKey ? firstListItem : lastListItem;
                // reset all on manual page refresh
                _this.clearSelection(activeItems);
            } else {
                toActiveItem = directionalNextItem ?? lastActivatedItem;

                if (toActiveItem) {
                    _this.clearSelection(activeItems);
                    if (toActiveItem.classList.contains('page-separator')) {
                        toActiveItem = _this.getDirectionalNext(toActiveItem, event.key);
                    }
                }
            }

            if (! toActiveItem) {
                return;
            }

            toActiveItems.push(toActiveItem);

            for (let groupList of groupLists) {
                toDeactivateItems.push(..._this.getActiveItems(groupList));

                let groupListItem = groupList.querySelector(`[data-action-item][data-icinga-detail-filter='${ toActiveItem.getAttribute('data-icinga-detail-filter') }']`);
                if (groupListItem !== null) {
                    toActiveItems.push(groupListItem);
                }
            }

            _this.clearSelection(toDeactivateItems);
            _this.setActive(toActiveItems);
            _this.setLastActivatedItemUrl(
                markAsLastActive ? markAsLastActive.dataset.icingaDetailFilter : toActiveItem.dataset.icingaDetailFilter
            );
            _this.scrollItemIntoView(toActiveItem, event.key);
            _this.loadDetailUrl(list);
        }

        /**
         * Get the next list item according to the pressed key (`ArrowUp` or `ArrowDown`)
         *
         * @param item The list item from which we want the next item
         * @param eventKey Pressed key (`ArrowUp` or `ArrowDown`)
         *
         * @returns {Element|null}
         */
        getDirectionalNext(item, eventKey) {
            if (! item) {
                return null;
            }

            return eventKey === 'ArrowUp' ? item.previousElementSibling : item.nextElementSibling;
        }

        /**
         * Find the list item that should be activated next
         *
         * @param lastActivatedItem
         * @param eventKey Pressed key (`ArrowUp` or `ArrowDown`)
         *
         * @returns {Element[]}
         */
        findToActiveItem(lastActivatedItem, eventKey) {
            let toActiveItem;
            let markAsLastActive;

            toActiveItem = this.getDirectionalNext(lastActivatedItem, eventKey);

            while (toActiveItem) {
                if (! toActiveItem.classList.contains('active')) {
                    break;
                }

                toActiveItem = this.getDirectionalNext(toActiveItem, eventKey);
            }

            markAsLastActive = toActiveItem;
            // if the next/previous sibling element is already active,
            // mark the last/first active element in list as last active
            while (markAsLastActive && this.getDirectionalNext(markAsLastActive, eventKey)) {
                if (! this.getDirectionalNext(markAsLastActive, eventKey).classList.contains('active')) {
                    break;
                }

                markAsLastActive = this.getDirectionalNext(markAsLastActive, eventKey);
            }

            return [toActiveItem, markAsLastActive];
        }

        /**
         * Select All list items
         *
         * @param list The action list
         */
        selectAll(list) {
            let allItems = this.getAllItems(list);
            let activeItems = this.getActiveItems(list);
            this.setActive(allItems.filter(item => ! activeItems.includes(item)));
            this.setLastActivatedItemUrl(allItems[allItems.length - 1].dataset.icingaDetailFilter);
            this.loadDetailUrl(list);
        }

        /**
         * Clear the selection by removing .active class
         *
         * @param selectedItems The items with class active
         */
        clearSelection(selectedItems) {
            selectedItems.forEach(item => item.classList.remove('active'));
        }

        /**
         * Set the last activated item Url
         *
         * @param url
         */
        setLastActivatedItemUrl(url) {
            this.lastActivatedItemUrl = url;
        }

        /**
         * Scroll the given item into view
         *
         * @param item Item to scroll into view
         * @param pressedKey Pressed key (`ArrowUp` or `ArrowDown`)
         */
        scrollItemIntoView(item, pressedKey) {
            let directionalNext = this.getDirectionalNext(item, pressedKey);

            if ("isDisplayContents" in item.parentElement.dataset) {
                item = item.firstChild;
                directionalNext = directionalNext ? directionalNext.firstChild : null;
            }
            // required when ArrowUp is pressed in new list OR after selecting all items with ctrl+A
            item.scrollIntoView({block: "nearest"});

            if (directionalNext) {
                directionalNext.scrollIntoView({block: "nearest"});
            }
        }

        clearDashboardSelections(dashboard, currentList) {
            dashboard.querySelectorAll('.action-list-kubernetes').forEach(otherList => {
                if (otherList !== currentList) {
                    this.clearSelection(this.getActiveItems(otherList));
                }
            })
        }

        /**
         * Load the detail url with selected items
         *
         * @param list The action list
         * @param anchorUrl If any anchor is clicked (e.g. host in service list)
         */
        loadDetailUrl(list, anchorUrl = null) {
            let url = anchorUrl;
            let activeItems = this.getActiveItems(list);

            if (url === null) {
                let anchor = activeItems[0].querySelector('[href]');
                url = anchor ? anchor.getAttribute('href') : null;
            }

            if (url === null) {
                return;
            }

            const suspendedContainer = this.suspendAutoRefresh(list);

            clearTimeout(this.lastTimeoutId);
            this.lastTimeoutId = setTimeout(() => {
                const requestNo = this.lastTimeoutId;
                this.activeRequests[requestNo] = suspendedContainer;
                this.lastTimeoutId = null;

                let req = this.icinga.loader.loadUrl(
                    url,
                    this.icinga.loader.getLinkTargetFor($(activeItems[0]))
                );

                req.always((_, __, errorThrown) => {
                    if (errorThrown !== 'abort') {
                        this.enableAutoRefresh(this.activeRequests[requestNo]);
                    }

                    delete this.activeRequests[requestNo];
                });
            }, 250);
        }

        /**
         * Add .active class to given list item
         *
         * @param toActiveItem The list item(s)
         */
        setActive(toActiveItem) {
            if (toActiveItem instanceof HTMLElement) {
                toActiveItem.classList.add('active');
            } else {
                toActiveItem.forEach(item => item.classList.add('active'));
            }
        }

        /**
         * Get the active items from given list
         *
         * @param list The action list
         *
         * @return array
         */
        getActiveItems(list) {
            let items;
            if (list.tagName.toLowerCase() === 'table') {
                items = list.querySelectorAll(':scope > tbody > [data-action-item].active');
            } else {
                items = list.querySelectorAll(':scope > [data-action-item].active');
            }

            return Array.from(items);
        }

        /**
         * Get all available items from given list
         *
         * @param list The action list
         *
         * @return array
         */
        getAllItems(list) {
            let items;
            if (list.tagName.toLowerCase() === 'table') {
                items = list.querySelectorAll(':scope > tbody > [data-action-item]');
            } else {
                items = list.querySelectorAll(':scope > [data-action-item]');
            }

            return Array.from(items);
        }

        onColumnClose(event) {
            let _this = event.data.self;
            let list = _this.findDetailUrlActionList(document.getElementById('col1'));
            if (list && list.matches('[data-icinga-detail-url]')) {
                _this.clearSelection(_this.getActiveItems(list));
            }
        }

        /**
         * Find the action list using the detail url
         *
         * @param {Element} container
         *
         * @return Element|null
         */
        findDetailUrlActionList(container) {
            let detailUrl = this.icinga.utils.parseUrl(
                this.icinga.history.getCol2State().replace(/^#!/, '')
            );

            let detailItem = container.querySelector(
                '[data-icinga-detail-filter="'
                + detailUrl.query.replace('?', '') + '"]'
            );

            return detailItem ? detailItem.parentElement : null;
        }

        /**
         * Triggers when column is moved to left or right
         *
         * @param event
         * @param sourceId The content is moved from
         */
        onColumnMoved(event, sourceId) {
            let _this = event.data.self;

            if (event.target.id === 'col2' && sourceId === 'col1') { // only for browser-back (col1 shifted to col2)
                _this.clearSelection(event.target.querySelectorAll('.action-list-kubernetes .active'));
            } else if (event.target.id === 'col1' && sourceId === 'col2') {
                for (const requestNo of Object.keys(_this.activeRequests)) {
                    if (_this.activeRequests[requestNo] === sourceId) {
                        _this.enableAutoRefresh(_this.activeRequests[requestNo]);
                        _this.activeRequests[requestNo] = _this.suspendAutoRefresh(event.target);
                    }
                }
            }
        }

        onRendered(event, isAutoRefresh) {
            let _this = event.data.self;
            let container = event.target;
            let isTopLevelContainer = container.matches('#main > :scope');

            let list;
            let groupLists;
            if (event.currentTarget !== container || Object.keys(_this.activeRequests).length) {
                // Nested containers are not processed multiple times || still processing selection/navigation request
                return;
            } else if (isTopLevelContainer && container.id !== 'col1') {
                if (isAutoRefresh) {
                    return;
                }

                // only for browser back/forward navigation
                list = _this.findDetailUrlActionList(document.getElementById('col1'));
            } else {
                list = _this.findDetailUrlActionList(container);
            }

            if (! list || ! ("isDisplayContents" in list.dataset)) {
                // no detail view || ignore when already set
                let actionLists = null;
                if (! list) {
                    actionLists = document.querySelectorAll('.action-list-kubernetes');
                } else {
                    actionLists = [list];
                }

                for (let actionList of actionLists) {
                    let firstSelectableItem = actionList.querySelector(':scope > [data-action-item]');
                    if (
                        firstSelectableItem
                        && (
                            ! firstSelectableItem.checkVisibility()
                            && firstSelectableItem.firstChild
                            && firstSelectableItem.firstChild.checkVisibility()
                        )
                    ) {
                        actionList.dataset.isDisplayContents = "";
                    }
                }
                groupLists = Array.from(document.querySelectorAll(`.action-list-kubernetes[data-list-group=${ actionLists[0].getAttribute('data-list-group') }]`)).filter(item => item !== actionLists[0]);
            } else {
                groupLists = Array.from(document.querySelectorAll(`.action-list-kubernetes[data-list-group=${ list.getAttribute('data-list-group') }]`)).filter(item => item !== list);
            }

            if (sessionStorage.getItem('active-column') === '#' + event.target.id) {
                let contentId = document.querySelector(_this.icinga.utils.getCSSPath(event.target) + ' > .content')?.id

                if (contentId !== undefined) {
                    sessionStorage.setItem('active-list', '#' + contentId + ' >' + sessionStorage.getItem('active-list').split('>')[1])
                }
            }

            if (list && list.matches('[data-icinga-detail-url]')) {
                let detailUrl = _this.icinga.utils.parseUrl(
                    _this.icinga.history.getCol2State().replace(/^#!/, '')
                );
                let toActiveItems = [];
                if (_this.matchesDetailUrl(list.dataset.icingaDetailUrl, detailUrl.path)) {
                    let item = list.querySelector(
                        '[data-icinga-detail-filter="' + detailUrl.query.slice(1) + '"]'
                    );
                    if (item) {
                        toActiveItems.push(item);
                        for (let groupList of groupLists) {
                            let groupListItem = groupList.querySelector(`[data-action-item][data-icinga-detail-filter='${ item.getAttribute('data-icinga-detail-filter') }']`)
                            if (groupListItem !== null) {
                                toActiveItems.push(groupListItem)
                            }
                        }
                    }
                }

                let dashboard = list.closest('.dashboard');
                if (dashboard) {
                    _this.clearDashboardSelections(dashboard, list);
                }

                _this.clearSelection(_this.getAllItems(list).filter(item => ! toActiveItems.includes(item)));
                _this.setActive(toActiveItems);
            }

            let lastLocation = sessionStorage.getItem('location');
            let currentLocation = window.location.href.split('?')[0];

            sessionStorage.setItem('location', currentLocation);

            if (currentLocation !== lastLocation) {
                sessionStorage.removeItem('active-column')
                sessionStorage.removeItem('active-list')
            }
        }

        matchesDetailUrl(itemUrl, detailUrl) {
            if (itemUrl === detailUrl) {
                return true;
            }

            // The slash is used to avoid false positives (e.g. icingadb/hostgroup and icingadb/host)
            return detailUrl.startsWith(itemUrl + '/');
        }

        onRenderedReorder(event) {
            if (event.target !== event.currentTarget) {
                return; // Nested containers are not of interest
            }

            const favoriteList = event.target.querySelector('[favorite-list]');
            if (! favoriteList) {
                return;
            }

            Sortable.create(favoriteList, {
                scroll: true,
                direction: 'vertical',
                draggable: '.list-item'
            });
        }

        onDropReorder(event) {
            event = event.originalEvent;
            if (event.to === event.from && event.newIndex === event.oldIndex) {
                // The user dropped the rotation at its previous position
                return;
            }

            const nextRow = event.item.nextSibling;

            let newPriority;
            if (event.oldIndex > event.newIndex) {
                // The rotation was moved up
                newPriority = Number(nextRow.querySelector(':scope > form').priority.value);
            } else {
                // The rotation was moved down
                if (nextRow !== null && nextRow.matches('.list-item')) {
                    newPriority = Number(nextRow.querySelector(':scope > form').priority.value) + 1;
                } else {
                    newPriority = '0';
                }
            }

            const form = event.item.querySelector(':scope > form');
            form.priority.value = newPriority;
            form.requestSubmit();
        }
    }

    Icinga.Behaviors.ActionList = ActionList;

}(Icinga));
