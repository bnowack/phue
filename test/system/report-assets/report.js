var report = {

    init: function () {
        report.data = JSON.parse(document.querySelector('#report-data').innerText);
        report.buildReport();
    },

    buildReport: function () {
        var $report = document.querySelector('#report');
        $report.innerHTML = '<nav id="feature-list"></nav><section id="results"></section>';

        report.buildNav();
        report.buildResults();
        report.injectStats();
    },

    /** @var Object entry */
    /** @var Object entry.feature */
    buildNav: function () {
        var $nav = document.querySelector('nav#feature-list');
        report.data.forEach(function (entry) {
            var $feature = $nav.querySelector('[data-file="' + entry.feature.file + '"]');
            if (!$feature) {
                $feature = document.createElement('div');
                $feature.setAttribute('data-file', entry.feature.file);
                var $link = document.createElement('a');
                $link.classList.add('feature');
                $link.href = entry.feature.file;
                $link.innerHTML = entry.feature.title;
                $link.addEventListener('click', report.onFeatureClick);
                $feature.appendChild($link);
                $nav.appendChild($feature);
            }
        });
    },

    onFeatureClick: function (event) {
        event.preventDefault();

        // deselect selected nav item
        var $selected = document.querySelector('nav#feature-list .selected');
        if ($selected) {
            $selected.classList.remove('selected');
        }

        // select unless previously selected
        var selectedFeature = null;
        if ($selected !== event.target.parentNode) {
            event.target.parentNode.classList.add('selected');
            selectedFeature = event.target.parentNode.getAttribute('data-file');
        }

        // hide all except selected
        var $features = document.querySelectorAll('#results .feature');
        for (var i = 0; i < $features.length; i++) {
            // make previously hidden feature visible again
            $features[i].classList.remove('deselected');

            // hide feature if we got a selection and the selection differs from the feature
            if (selectedFeature && selectedFeature !== $features[i].getAttribute('data-file')) {
                $features[i].classList.add('deselected');
            }
        }
        document.querySelector('#results').scrollIntoView(true);
    },

    /** @var Object entry */
    /** @var Object entry.step */
    /** @var String entry.step.keyword */
    /** @var String entry.step.screenshot */
    buildResults: function () {
        report.data.forEach(function (entry) {
            var $step = report.getStepNode(entry);
            $step.classList.add(entry.step.result);

            var $keyword = document.createElement('span');
            $keyword.classList.add('keyword');
            $keyword.innerHTML = entry.step.keyword;
            $step.appendChild($keyword);

            var $text = document.createElement('span');
            $text.classList.add('text');
            $text.innerHTML = entry.step.text;
            $step.appendChild($text);

            var $result = document.createElement('span');
            $result.classList.add('result');
            var time = new Date(entry.step.timestamp * 1000);
            $result.innerHTML = entry.step.result + ' at ' + time.toLocaleTimeString() + ' on ' + time.toDateString();
            $step.appendChild($result);

            if (entry.step.screenshot) {
                var $img = document.createElement('img');
                $img.src = entry.step.screenshot;
                $result.appendChild($img);
            }
        });
    },

    getStepNode: function (entry) {
        var $scenario = report.getScenarioNode(entry);

        var $step = document.createElement('div');
        $step.classList.add('step');
        $scenario.appendChild($step);

        return $step;
    },

    /** @var Object entry */
    /** @var Object entry.scenario */
    getScenarioNode: function (entry) {
        var $feature = report.getFeatureNode(entry);
        var $scenario = $feature.querySelector('[data-title="' + entry.scenario.title.replace(/"/g, '\\"') + '"]');
        if (!$scenario) {
            $scenario = document.createElement('div');
            $scenario.setAttribute('data-title', entry.scenario.title);
            $scenario.classList.add('scenario');
            $scenario.innerHTML = '<h3>' + entry.scenario.title + '</h3>';
            $feature.appendChild($scenario);
        }
        return $scenario;
    },

    getFeatureNode: function (entry) {
        var $results = document.querySelector('#results');
        var $feature = $results.querySelector('[data-file="' + entry.feature.file + '"]');
        if (!$feature) {
            $feature = document.createElement('div');
            $feature.setAttribute('data-file', entry.feature.file);
            $feature.classList.add('feature');
            $feature.innerHTML = '<h2>' + entry.feature.title + '</h2>';
            $results.appendChild($feature);
        }
        return $feature;
    },

    injectStats: function () {
        // flag scenarios
        var $scenarios = document.querySelectorAll('#results .scenario');
        report.injectTypedStats($scenarios, '.step');
        // flag features
        var $features = document.querySelectorAll('#results .feature');
        report.injectTypedStats($features, '.scenario');
    },

    injectTypedStats: function ($containers, resultSelector) {
        for (var i = 0; i < $containers.length; i++) {
            var $container = $containers[i];
            var $stats = document.createElement('div');
            $stats.className = 'stats';
            $container.insertBefore($stats, $container.firstChild);
            var resultCount = $container.querySelectorAll(resultSelector).length;
            var stats = {
                passed: $container.querySelectorAll(resultSelector + '.passed').length,
                failed: $container.querySelectorAll(resultSelector + '.failed').length,
                skipped: $container.querySelectorAll(resultSelector + '.skipped').length,
                pending: $container.querySelectorAll(resultSelector + '.pending').length
            };
            for (var label in stats) {
                if (!stats.hasOwnProperty(label)) {
                    continue;
                }
                var value = stats[label];
                report.injectStatsBadge($stats, label, value, resultCount);
                // flag container
                if (resultCount === value) {// e.g. all passed => flag container as passed
                    $container.classList.add(label);
                }
            }
            // any failed => flag container as failed
            if (stats.failed !== 0) {
                $container.classList.add('failed');
            }
            // flag nav items
            if ($container.classList.contains('feature')) {
                var featureFile = $container.getAttribute('data-file');
                var $navItem = document.querySelector('#feature-list .feature[href="' + featureFile + '"]');
                if ($navItem && $container.classList.contains('passed')) {
                    $navItem.classList.add('passed');
                } else if ($navItem && $container.classList.contains('failed')) {
                    $navItem.classList.add('failed');
                } else {
                    $navItem.classList.add('incomplete');
                }
            }
        }
    },

    injectStatsBadge: function ($stats, label, value, stepCount) {
        var $badge = document.createElement('span');
        $badge.classList.add('badge');
        $badge.classList.add(label);
        $badge.setAttribute('data-count', value);
        $badge.innerHTML = value + '<span class="total"> of ' + stepCount + '</span> ' + label;
        $stats.appendChild($badge);
    }
};

setTimeout(report.init, 100);
