// Leaflet + markercluster — wrapper lazy-chunk (di-load via app.js ensureMaps).
//
// PENTING: markercluster (UMD) mereferensikan `L` GLOBAL di dalam factory-nya
// (`var t = L.MarkerClusterGroup = ...`). Supaya binding-nya benar, leaflet dan
// markercluster HARUS berada dalam SATU module graph statis seperti dahulu di
// app.js — jangan pisah jadi dynamic import terpisah (L akan undefined saat
// chunk dievaluasi). File ini hanya di-import secara dinamis oleh app.js
// (window.ensureMaps), jadi kode Leaflet tidak ikut bundle awal.
import L from "leaflet";
import "leaflet.markercluster";
import "leaflet/dist/leaflet.css";
import "leaflet.markercluster/dist/MarkerCluster.css";
import "leaflet.markercluster/dist/MarkerCluster.Default.css";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

window.L = L;

export default L;
