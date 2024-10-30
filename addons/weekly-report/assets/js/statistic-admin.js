; (($ ,hm , dt) => {
    "use strict";

    const _storageKey = "statisticAdmin";

    $(document).ready(() => {

        const cache = hm.readFromLocalStorage( _storageKey );
        const data = cache?.data;

        if (data) {
            createChart(data);
            return;
        }

        $.post(dt['ajaxurl'], {
            'action': 'heimdall_weekly_report',
            '_wpnonce': dt['ajaxnonce']
        }, (res) => {
            
            var yarray = [0, 0, 0, 0, 0, 0, 0];
            var zarray = [0, 0, 0, 0, 0, 0, 0];
            var warray = [0, 0, 0, 0, 0, 0, 0];
            var parray = [0, 0, 0, 0, 0, 0, 0];

            res.data.forEach((e, i) => {
                var ind = parseInt(e['x']);
                yarray[ind] = parseInt(e['y']);
                zarray[ind] = parseInt(e['z']);
                warray[ind] = parseInt(e['w']);
                parray[ind] = parseInt(e['p']);
            });

            var dtset = [{
                label: 'Total',
                backgroundColor: '#00325b',
                stack: 'Stack 1',
                data: zarray
            }, {
                label: 'Unique Visitors',
                backgroundColor: '#005171',
                stack: 'Stack 2',
                data: yarray
            }, {
                label: 'Home Page',
                backgroundColor: '#ffe06a',
                stack: 'Stack 3',
                data: parray
            }];

            if (dt['is_multisite'] == '1') {
                dtset.push({
                    label: 'This Blog',
                    backgroundColor: '#fd5a35',
                    stack: 'Stack 4',
                    data: warray
                });
            }

            hm.updateLocalStorage( _storageKey ,  dtset, 60 * 60 * 1000);
            
            createChart(dtset);    
        });

    });


    function createChart(data){

        const ctx = $("#statisticChart");

        ctx.parents('.busy').removeClass('busy');

        new Chart(ctx[0], {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: data
            },
            options: {
                tooltips: {
                    mode: 'index',
                    intersect: false,
                    cornerRadius: 4,
                    footerFontColor: '#ccc',
                    footerFontStyle: 'normal'
                },
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) { if (value % 1 === 0) { return value; } }
                        }
                    }
                }
            }
        });
    }


})(jQuery , Heimdall , HeimdallData);