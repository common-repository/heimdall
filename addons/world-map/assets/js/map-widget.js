; (($, hm, dt) => {
  "use strict";

  const _storageKey = "worldMap";

  const init = function (mapData) {
    $.getJSON(dt["worldMapDataURL"])
      .done((cdata) => {
        const countries = ChartGeo.topojson.feature(cdata, cdata.objects.countries).features;
        createChart(mapData, countries);
      })
      .fail((jqxhr, textStatus, error) => {
        var err = textStatus + ", " + error;
        console.error( "Request Failed: " + err );
      });
  }

  $(document).ready(() => {

    const cache = hm.readFromLocalStorage(_storageKey);
    const mapData = cache?.data;

    if (mapData) {
      init(mapData);
      return;
    }

    $.post(dt['ajaxurl'], {
      'action': 'heimdall_world_map',
      '_wpnonce': dt['ajaxnonce']
    }, (res) => {

      const newData = res.data['world_map_data'];
      hm.updateLocalStorage(_storageKey, newData, 60 * 60 * 1000);
      init(newData);

    });

  });


  function createChart(data, countries) {

    const container = $("#statisticsWorldMapDataContainer");

    let mapData = []

    countries.forEach(element => {
      let records = 0;
      let country = data.find(x => x.n == element.properties.name);

      if (country) {
        records = parseInt(country.r);
      }

      mapData.push({
        feature: element,
        country: element.properties.name,
        records: records
      });
    });

    const chart = new Chart(container[0].getContext("2d"), {
      type: 'choropleth',
      data: {
        labels: mapData.map((d) => d.country),
        datasets: [{
          label: 'Countries',
          data: mapData.map((d) => {
            return {
              feature: d.feature,
              value: d.records
            }
          })
        }]
      },
      options: {
        animation: {
          duration: 0
        },
        hover: {
          animationDuration: 0
        },
        responsiveAnimationDuration: 0,
        showOutline: true,
        showGraticule: false,
        plugins: {
          legend: {
            display: false
          },
        },
        scales: {
          xy: {
            projection: ChartGeo.geoEquirectangular,
            beginAtZero: true
          },
          color: {
            quantize: 5,
            legend: {
              position: 'bottom-left'
            }
          }
        }
      }
    });

    container.css('background', '#d4e4f9').parents('.busy').removeClass('busy');

  }


})(jQuery, Heimdall, HeimdallData);