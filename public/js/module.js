/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

(function (Icinga) {

    "use strict";

    class IcingaKubernetes extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            this.on('rendered', '.graph-container', this.onRendered, this);
        }

        onRendered(event, autorefresh, scripted, autosubmit) {
            let _this = event.data.self;
            let graphContainer = event.target;

            _this.renderChart(graphContainer);
        }

        renderChart(graphContainer) {
            let canvas = graphContainer.querySelector('canvas');
        }
    }

    Icinga.Behaviors.IcingaKubernetes = IcingaKubernetes;

})(Icinga);