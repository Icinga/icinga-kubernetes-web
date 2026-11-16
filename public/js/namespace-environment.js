/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

(function (Icinga) {

    "use strict";

    const SVG = 'http://www.w3.org/2000/svg';

    /**
     * Draw the links of the namespace environment tree that can't follow the tree
     *
     * Each node is drawn once. Its other parents name it in data-links, see NamespaceEnvironment::dedupe(). Those
     * links are drawn dashed into an SVG behind the nodes, entering the node's column from the left.
     */
    class NamespaceEnvironment extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            this.on('rendered', '#main > .container', this.onRendered, this);

            // Node widths depend on the container width, so links have to follow.
            this.resizeObserver = new ResizeObserver(entries => {
                for (const entry of entries) {
                    this.draw(entry.target);
                }
            });
        }

        onRendered(event) {
            // Handlers aren't bound to the behavior.
            const _this = event.data.self;

            for (const widget of event.target.querySelectorAll('.namespace-environment')) {
                _this.resizeObserver.observe(widget);
                _this.draw(widget);
            }
        }

        /**
         * (Re)draw the links of the given widget
         *
         * @param {Element} widget
         */
        draw(widget) {
            widget.querySelector(':scope > svg.links')?.remove();

            const sources = widget.querySelectorAll('li[data-links]');
            if (! sources.length) {
                return;
            }

            const em = parseFloat(getComputedStyle(widget).fontSize);

            // Source nodes of each target node.
            const sourcesOf = new Map();
            for (const source of sources) {
                for (const id of source.dataset.links.split(' ')) {
                    const target = widget.querySelector(`li[data-node="${id}"] > .node`);
                    if (target) {
                        sourcesOf.set(target, [...(sourcesOf.get(target) || []), source.querySelector(':scope > .node')]);
                    }
                }
            }

            // Each target gets its own vertical lane in the gap before its column, so every line leads to exactly
            // one object. Widen the gap between columns until lanes are at least 8px apart, before measuring.
            const lanesPerColumn = new Map();
            for (const target of sourcesOf.keys()) {
                const column = target.parentElement.dataset.column;
                lanesPerColumn.set(column, (lanesPerColumn.get(column) || 0) + 1);
            }

            const gap = Math.max(3 * em, (Math.max(...lanesPerColumn.values()) + 1) * 8 + 0.75 * em);
            widget.style.setProperty('--gap', `${gap}px`);

            const origin = widget.getBoundingClientRect();
            const x = rect => rect.left - origin.left + widget.scrollLeft;
            const y = rect => rect.top + rect.height / 2 - origin.top + widget.scrollTop;

            const targets = [];
            for (const [target, from] of sourcesOf) {
                targets.push({to: target.getBoundingClientRect(), from: from.map(node => node.getBoundingClientRect())});
            }

            const columns = new Map();
            for (const target of targets) {
                const column = Math.round(x(target.to));
                columns.set(column, [...(columns.get(column) || []), target]);
            }

            for (const [column, inColumn] of columns) {
                // Lanes ordered like their targets, so they cross each other as little as possible.
                inColumn.sort((a, b) => y(a.to) - y(b.to));

                const start = column - gap;
                const end = column - 0.75 * em; // Room for the arrow.
                const trunk = column - gap / 2; // The tree's own connector.
                inColumn.forEach((target, i) => {
                    target.lane = start + (end - start) * (i + 1) / (inColumn.length + 1);
                    if (Math.abs(target.lane - trunk) < 2) {
                        target.lane += 3;
                    }
                });
            }

            const svg = document.createElementNS(SVG, 'svg');
            svg.classList.add('links');
            svg.setAttribute('width', widget.scrollWidth);
            svg.setAttribute('height', widget.scrollHeight);
            svg.innerHTML = '<defs><marker id="namespace-environment-arrow" viewBox="0 0 10 10" refX="10" refY="5"'
                + ' markerWidth="8" markerHeight="8" orient="auto"><path d="M 0 0 L 10 5 L 0 10 z"/></marker></defs>';

            const add = (tag, attributes) => {
                const element = document.createElementNS(SVG, tag);
                for (const [name, value] of Object.entries(attributes)) {
                    element.setAttribute(name, value);
                }

                svg.append(element);
            };

            for (const {to, from, lane} of targets) {
                const ys = [y(to), ...from.map(y)];
                add('path', {d: `M ${lane} ${Math.min(...ys)} V ${Math.max(...ys)}`});
                add('path', {d: `M ${lane} ${y(to)} H ${x(to)}`, 'marker-end': 'url(#namespace-environment-arrow)'});

                for (const rect of from) {
                    add('path', {d: `M ${x(rect) + rect.width} ${y(rect)} H ${lane}`});
                    // Where a source joins the lane, to tell joins from crossings.
                    add('circle', {cx: lane, cy: y(rect), r: 2.5});
                }
            }

            widget.prepend(svg);
        }
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.NamespaceEnvironment = NamespaceEnvironment;

})(Icinga);
