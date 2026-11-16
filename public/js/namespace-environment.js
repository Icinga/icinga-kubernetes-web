/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

(function (Icinga) {

    "use strict";

    const SVG = 'http://www.w3.org/2000/svg';

    /**
     * Draw the edges of the namespace environment tree, and highlight a hovered node's neighbours
     *
     * All edges are drawn into one SVG behind the nodes, so they line up exactly:
     * - Tree edges from a node to each child, forking at the children's left edge.
     * - Links from the other parents of a node drawn only once, named in data-links, see
     *   NamespaceEnvironment::dedupe(). Each linked node gets its own vertical lane in the gap before its column,
     *   with a dot where a source joins it.
     * - One arrowhead per node with incoming edges.
     */
    class NamespaceEnvironment extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            this.on('rendered', '#main > .container', this.onRendered, this);

            // Node widths depend on the container width, so edges have to follow.
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

                widget.addEventListener('mouseover', e => {
                    const node = e.target.closest('li[data-node] > .node');
                    if (node !== widget.hoveredNode) {
                        widget.hoveredNode = node;
                        _this.highlight(widget, node?.parentElement);
                    }
                });
                widget.addEventListener('mouseleave', () => {
                    widget.hoveredNode = null;
                    _this.highlight(widget, null);
                });
            }
        }

        /**
         * Create an SVG element with the given attributes
         *
         * @param {string} tag
         * @param {Object} attributes
         *
         * @returns {Element}
         */
        svgElement(tag, attributes) {
            const element = document.createElementNS(SVG, tag);
            for (const [name, value] of Object.entries(attributes)) {
                element.setAttribute(name, value);
            }

            return element;
        }

        /**
         * (Re)draw the edges of the given widget
         *
         * @param {Element} widget
         */
        draw(widget) {
            widget.querySelector(':scope > svg.edges')?.remove();

            const em = parseFloat(getComputedStyle(widget).fontSize);
            const nodeLi = id => widget.querySelector(`li[data-node="${id}"]`);
            const node = li => li.querySelector(':scope > .node, :scope > .summary');

            // Sources of each linked node, by its id.
            const sourcesOf = new Map();
            for (const source of widget.querySelectorAll('li[data-links]')) {
                for (const id of source.dataset.links.split(' ')) {
                    if (nodeLi(id)) {
                        sourcesOf.set(id, [...(sourcesOf.get(id) || []), source]);
                    }
                }
            }

            // Widen the gap between columns until lanes are at least 8px apart, before measuring.
            const lanesPerColumn = new Map();
            for (const id of sourcesOf.keys()) {
                const column = nodeLi(id).dataset.column;
                lanesPerColumn.set(column, (lanesPerColumn.get(column) || 0) + 1);
            }

            const gap = Math.max(3 * em, (Math.max(0, ...lanesPerColumn.values()) + 1) * 8 + 0.75 * em);
            widget.style.setProperty('--gap', `${gap}px`);

            // Coordinates relative to the SVG, which is at the widget's padding box and scrolls with its content.
            // Snapped to device pixels, so 1px lines are sharp and lines on top of each other match.
            const box = widget.getBoundingClientRect();
            const originX = box.left + widget.clientLeft - widget.scrollLeft;
            const originY = box.top + widget.clientTop - widget.scrollTop;
            const dpr = window.devicePixelRatio || 1;
            const snap = (value, origin) => (Math.round(value * dpr - dpr / 2) + dpr / 2) / dpr - origin;
            // Edges end at the outline of an element, e.g. of the focused node, instead of under it.
            const outline = element => {
                const style = getComputedStyle(element);

                return style.outlineStyle === 'none'
                    ? 0
                    : parseFloat(style.outlineWidth) + parseFloat(style.outlineOffset);
            };
            const left = element => snap(element.getBoundingClientRect().left - outline(element), originX);
            const right = element => snap(element.getBoundingClientRect().right + outline(element), originX);
            const middle = element => {
                const rect = element.getBoundingClientRect();

                return snap(rect.top + rect.height / 2, originY);
            };

            const svg = this.svgElement('svg', {
                class: 'edges',
                width: widget.scrollWidth,
                height: widget.scrollHeight
            });

            // Nodes with incoming edges, which get an arrowhead.
            const entered = new Set();

            // Tree edges, tagged with the ids of parent and child for highlight().
            for (const parent of widget.querySelectorAll('li[data-node]')) {
                for (const child of parent.querySelectorAll(':scope > ul > li')) {
                    svg.append(this.svgElement('path', {
                        d: `M ${right(node(parent))} ${middle(node(parent))} H ${left(child)}`
                            + ` V ${middle(node(child))} H ${left(node(child))}`,
                        'data-parent': parent.dataset.node,
                        'data-child': child.dataset.node || ''
                    }));
                    entered.add(child);
                }
            }

            // Lanes, each in the gap before its target's column, ordered like the targets, so they cross each other as
            // little as possible.
            const columns = new Map();
            for (const id of sourcesOf.keys()) {
                // Without outlines, so all nodes of a column share it.
                const column = snap(node(nodeLi(id)).getBoundingClientRect().left, originX);
                columns.set(column, [...(columns.get(column) || []), id]);
            }

            const lanes = new Map();
            for (const [column, ids] of columns) {
                ids.sort((a, b) => middle(node(nodeLi(a))) - middle(node(nodeLi(b))));

                const start = column - gap;
                const end = column - 0.75 * em; // Room for the arrowhead.
                const fork = column - gap / 2; // Where tree edges fork.
                ids.forEach((id, i) => {
                    let lane = start + (end - start) * (i + 1) / (ids.length + 1);
                    if (Math.abs(lane - fork) < 2) {
                        lane += 3;
                    }

                    lanes.set(id, snap(lane + originX, originX));
                });
            }

            // Links, one path per source along its target's lane, tagged with the ids of source and target.
            for (const [id, sources] of sourcesOf) {
                const target = nodeLi(id);
                const lane = lanes.get(id);
                entered.add(target);

                for (const source of sources) {
                    const y = middle(node(source));
                    svg.append(
                        this.svgElement('path', {
                            d: `M ${right(node(source))} ${y} H ${lane} V ${middle(node(target))}`
                                + ` H ${left(node(target))}`,
                            'data-source': source.dataset.node,
                            'data-target': id
                        }),
                        // Where a source joins the lane, to tell joins from crossings.
                        this.svgElement('circle', {
                            cx: lane,
                            cy: y,
                            r: 2.5,
                            'data-source': source.dataset.node,
                            'data-target': id
                        })
                    );
                }
            }

            for (const li of entered) {
                const x = left(node(li));
                const y = middle(node(li));
                svg.append(this.svgElement('path', {
                    class: 'arrowhead',
                    d: `M ${x - 0.5 * em} ${y - 0.3 * em} L ${x} ${y} L ${x - 0.5 * em} ${y + 0.3 * em} Z`,
                    'data-node': li.dataset.node || ''
                }));
            }

            widget.prepend(svg);

            if (widget.hoveredNode) {
                this.highlight(widget, widget.hoveredNode.parentElement);
            }
        }

        /**
         * Highlight the given node, its direct neighbours and the edges to them, or remove the highlight
         *
         * Neighbours are the node's parent and children in the tree, the nodes it links to and the nodes linking to it.
         *
         * @param {Element} widget
         * @param {?Element} li
         */
        highlight(widget, li) {
            for (const element of widget.querySelectorAll('.highlight')) {
                element.classList.remove('highlight');
            }

            if (li === null) {
                return;
            }

            const svg = widget.querySelector(':scope > svg.edges');
            const id = li.dataset.node;
            const nodeLi = nodeId => widget.querySelector(`li[data-node="${nodeId}"]`);

            const neighbours = [li];
            // Nodes a highlighted edge leads into, whose arrowhead is highlighted.
            const entered = [];
            const edges = [`[data-parent="${id}"]`, `[data-child="${id}"]`, `[data-source="${id}"]`];

            const parent = li.parentElement.closest('li[data-node]');
            if (parent && widget.contains(parent)) {
                neighbours.push(parent);
                entered.push(id);
            }

            for (const child of li.querySelectorAll(':scope > ul > li[data-node]')) {
                neighbours.push(child);
                entered.push(child.dataset.node);
            }

            for (const target of (li.dataset.links || '').split(' ').filter(Boolean)) {
                if (nodeLi(target)) {
                    neighbours.push(nodeLi(target));
                    entered.push(target);
                }
            }

            const sources = widget.querySelectorAll(`li[data-links~="${id}"]`);
            if (sources.length) {
                neighbours.push(...sources);
                entered.push(id);
                edges.push(`[data-target="${id}"]`);
            }

            for (const neighbour of neighbours) {
                neighbour.classList.add('highlight');
            }

            for (const element of svg.querySelectorAll(
                [...edges.map(edge => `${edge}:not(.arrowhead)`), ...entered.map(e => `.arrowhead[data-node="${e}"]`)]
                    .join(', ')
            )) {
                element.classList.add('highlight');
                // To the end, so edges sharing a stretch, e.g. siblings' edges up to their fork, don't cover it.
                svg.append(element);
            }
        }
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.NamespaceEnvironment = NamespaceEnvironment;

})(Icinga);
