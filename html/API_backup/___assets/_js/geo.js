var geo = {};

geo.distanceBetweenPoints = function (point1, point2) {
  var lat1 = point1[0];
  var lat2 = point2[0];
  var lon1 = point1[0];
  var lon2 = point2[0];

  var R = 6371e3; // metres
  var φ1 = lat1.toRadians();
  var φ2 = lat2.toRadians();
  var Δφ = (lat2 - lat1).toRadians();
  var Δλ = (lon2 - lon1).toRadians();

  var a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
          Math.cos(φ1) * Math.cos(φ2) *
          Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
  var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

  var d = R * c;
  return d;
};

geo.get_zone = function(lat, lon) {
	var minDis = 0.5; // Distance between spaces in km
	var kpd = 111.19483272; // Kilometer per degree

	var vSpace = Math.round(lat * (kpd / minDis));
	var ccvs = vSpace / (kpd / minDis); // Center coordinate in space

	var cos = Math.cos((ccvs * Math.PI) / 180);
	var hSpace = Math.round((lon * (kpd / minDis) * cos));

	return [vSpace, hSpace];
};

geo.get_zone_center = function(zone) {
  var minDis = 0.5; // Distance between spaces in km
  var kpd = 111.19483272; // Kilometer per degree

  var vSpace = zone[0];
  var hSpace = zone[1];

  var lat = (vSpace * minDis) / kpd;
  var ccvs = vSpace / (kpd / minDis); // Center coordinate in space
  var cos = Math.cos((ccvs * Math.PI) / 180);
  var lon = (hSpace * minDis) / (kpd * cos);

  //var vSpace = Math.round(lat * (kpd / minDis));
  //var hSpace = Math.round((lon * (kpd / minDis) * cos));

  return [lat, lon];
};

/**
 * Get the zones surrounding the suplied
 */
geo.get_9_zones = function(zone) {
  var zones = [];

  var zone_dirs = [ // Zone directions
    [ 0,  0], // Center
    [ 1,  0], // N
    [ 1,  1], // NE
    [ 0,  1], // E
    [-1,  1], // SW
    [-1,  0], // S
    [-1, -1], // SW
    [ 0, -1], // W
    [ 1, -1], // NW
  ];

  for (var current_dir = 0; current_dir < zone_dirs.length; current_dir++) {
    zones.push(
      [
        zone[0] + zone_dirs[current_dir][0],
        zone[1] + zone_dirs[current_dir][1]
      ]
    );
  }

  return zones;
};
