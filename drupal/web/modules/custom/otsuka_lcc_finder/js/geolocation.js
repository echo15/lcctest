/**
 * @file
 * Geolocation add-ons.
 */

(function ($, Drupal, geolocation, settings) {
  'use strict';
  Drupal.behaviors.otsukaLccFinderGeolocation = {
    attach: function (context) {
      var self = this;
      if (
        $('.view-lcc-finder', context).length
        && settings.geolocation
        && settings.geolocation.commonMap
      ) {
        var processMarkers = function (map) {
          if (!window.google) {
            return;
          }
          window.google.maps.event.addListener(map.googleMap, 'center_changed', function () {
            self.processMarkers(map);
          });
          self.processMarkers(map);
        };
        for (var id in settings.geolocation.commonMap) {
          if (settings.geolocation.commonMap.hasOwnProperty(id)) {
            geolocation.addMapLoadedCallback(processMarkers, id);
          }
        }
      }
    },
    processMarkers: function (map) {
      map.mapMarkers = map.mapMarkers || {};

      for (var i in map.mapMarkers) {
        if (!map.mapMarkers.hasOwnProperty(i)) {
          continue;
        }
        var marker = map.mapMarkers[i];
        if (!marker.otsukaLccGeolocation) {
          marker.otsukaLccGeolocation = true;
          marker.index = i;
          marker.addListener('click', function (e) {
            var $rows = $('.view-lcc-finder').has(map.container).find('.views-row');
            var $container = $rows.parents('.views-form').eq(0);
            if (!$rows.get(this.index)) {
              return;
            }
            if ($container.css('position') === 'static') {
              $container.css('position', 'relative');
            }
            if ($container.prop('scrollHeight') > $container.height()) {
              $container.animate({
                scrollTop: $rows.eq(this.index).position().top + $container.scrollTop()
              });
            }
          });
        }
      }
    }
  };
})(jQuery, Drupal, Drupal.geolocation, drupalSettings);
