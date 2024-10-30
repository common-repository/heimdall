; (($, hm, dt) => {
    "use strict";

    const _storageKey = "todayAdmin";

    $(document).ready(() => {

        const cache = hm.readFromLocalStorage( _storageKey );
        const data = cache?.data;

        if (data) {
            createChart(data);
            return;
        }

        $.post(dt['ajaxurl'], {
            'action': 'heimdall_today_report',
            '_wpnonce': dt['ajaxnonce']
        }, (res) => {

            var now_hour = parseInt(res.data['today_now_hour']);

            var yarray = new Array(25).join('0').split('');
            var zarray = new Array(25).join('0').split('');
            var warray = new Array(25).join('0').split('');
            var parray = new Array(25).join('0').split('');

            res.data['today'].forEach((e, i) => {

                var ind = parseInt(e['x']);

                if (now_hour < ind) {
                    return;
                }

                yarray[ind] = parseInt(e['y']);
                zarray[ind] = parseInt(e['z']);
                warray[ind] = parseInt(e['w']);
                parray[ind] = parseInt(e['p']);
            });

            var dtset = [{
                label: 'Total',
                borderColor: '#00325b',
                backgroundColor: 'transparent',
                borderWidth: 2,
                data: zarray,
                pointRadius: 0,
                lineTension: 0,
                fill: false,
                cubicInterpolationMode: 'monotone',
                tension: 0.4
            }, {
                label: 'Unique Visitors',
                borderColor: '#005171',
                backgroundColor: 'transparent',
                borderWidth: 2,
                data: yarray,
                pointRadius: 0,
                lineTension: 0,
                fill: false,
                cubicInterpolationMode: 'monotone',
                tension: 0.4
            }, {
                label: 'Home Page',
                borderColor: '#ffe06a',
                backgroundColor: 'transparent',
                borderWidth: 2,
                data: parray,
                pointRadius: 0,
                lineTension: 0,
                fill: false,
                cubicInterpolationMode: 'monotone',
                tension: 0.4
            }];

            if (dt['is_multisite'] == '1') {
                dtset.push({
                    label: 'This Blog',
                    borderColor: '#fd5a35',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    data: warray,
                    pointRadius: 0,
                    lineTension: 0,
                    fill: false,
                    cubicInterpolationMode: 'monotone',
                    tension: 0.4
                });
            }

            hm.updateLocalStorage( _storageKey ,  dtset , 5 * 60 * 1000 );

            createChart(dtset);

        });

    });

    function createChart(dtset) {

        const ctx = $("#statisticsTodayChart");

        ctx.parents('.busy').removeClass('busy');

        new Chart(ctx[0], {
            type: 'line',
            data: {
                labels: Array.apply(null, { length: 24 }).map(Number.call, Number).map(v => _nttr(v) + " - " + _nttr(v+1)),
                datasets: dtset
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 5,
                        top: 20,
                        right: 5
                    }
                },
                interaction: {
                    intersect: false,
                    position: 'average',
                    titleAlign: 'center',
                    usePointStyle: true,
                    mode: 'index',
                    axis: 'x'
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            callback: _nttr
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function _nttr(v){
        return (v < 10 ? '0' + v : v) + ":00";
     }

})(jQuery, Heimdall, HeimdallData);