db.personas.insertMany([
{name: "Azahar del Solano", age: 55, address: {direccion: "Alameda Flora Díaz 774 Apt. 88", numero: 8873, ciudad: "Barcelona"}, telefono: "+34 742 031 103"},
{name: "Esperanza Andrés Villena", age: 55, address: {direccion: "Rambla Cecilio Rico 9", numero: 9538, ciudad: "Melilla"}, telefono: "+34704 219 582"},
{name: "Telmo Garriga-Priego", age: 28, address: {direccion: "Calle de Baudelio Aragonés 60 Piso 4", numero: 6578, ciudad: "Valencia"}, telefono: "+34 727 78 33 62"},
{name: "Piedad Goñi Vergara", age: 43, address: {direccion: "Plaza Amado Arregui 7 Puerta 1", numero: 7358, ciudad: "Madrid"}, telefono: "+34 724019788"},
{name: "Guiomar Lluch Abella", age: 42, address: {direccion: "Cañada Vicente Valls 27 Apt. 63", numero: 9594, ciudad: "Lugo"}, telefono: "+34 746413521"},
{name: "Amelia Juliana Verdugo Torrent", age: 23, address: {direccion: "Paseo Viviana Chaparro 82", numero: 5993, ciudad: "Murcia"}, telefono: "+34 706054452"},
{name: "Salud Bastida Montaña", age: 55, address: {direccion: "Alameda de Manolo Burgos 17", numero: 6164, ciudad: "Toledo"}, telefono: "+34703206675"},
{name: "Azeneth Costa", age: 20, address: {direccion: "Plaza Mariano Pont 32 Apt. 18", numero: 9100, ciudad: "Guipúzcoa"}, telefono: "+34899 26 81 22"},
{name: "Alba Tudela-Ferrán", age: 21, address: {direccion: "Alameda de Guiomar Carrillo 3 Apt. 71", numero: 4045, ciudad: "Cádiz"}, telefono: "+34719651001"},
{name: "Sara Casares Robledo", age: 38, address: {direccion: "Paseo Evangelina Avilés 65 Apt. 26", numero: 6789, ciudad: "Segovia"}, telefono: "+34 722 34 96 28"},
{name: "Basilio Ojeda Merino", age: 65, address: {direccion: "Pasadizo Jenaro Arribas 45 Apt. 81", numero: 1680, ciudad: "La Rioja"}, telefono: "+34718 442 564"},
{name: "Obdulia Adán Benito", age: 51, address: {direccion: "Cuesta Venceslás Manrique 21 Apt. 14", numero: 9630, ciudad: "Ceuta"}, telefono: "+34733 900 212"},
{name: "Juanita Torralba-Sánchez", age: 63, address: {direccion: "Pasaje de Pastora Ariza 57", numero: 1898, ciudad: "Valladolid"}, telefono: "+34 901 09 07 37"},
{name: "Adalberto Iriarte Luís", age: 60, address: {direccion: "Calle de Lorena Núñez 14", numero: 3793, ciudad: "Albacete"}, telefono: "+34 810448055"},
{name: "Roxana Bárcena", age: 29, address: {direccion: "Callejón Tristán Cobo 18 Piso 6", numero: 8981, ciudad: "Madrid"}, telefono: "+34701105043"},
{name: "Cecilio Ferrero Santamaría", age: 35, address: {direccion: "Rambla Alicia Manjón 6 Piso 2", numero: 2297, ciudad: "Ávila"}, telefono: "+34802 000 948"},
{name: "Úrsula Bermúdez Villaverde", age: 39, address: {direccion: "Vial de Encarnacion Cruz 36", numero: 2342, ciudad: "Cuenca"}, telefono: "+34885 123 104"},
{name: "Patricio Bernabé Segura Segura", age: 54, address: {direccion: "Paseo de Gala Adadia 96", numero: 6752, ciudad: "Castellón"}, telefono: "+34 741 32 49 08"},
{name: "Baltasar Campos Mora", age: 45, address: {direccion: "Acceso Héctor Feijoo 175", numero: 1276, ciudad: "Pontevedra"}, telefono: "+34 960 94 17 76"},
{name: "Clementina Cañas Lladó", age: 32, address: {direccion: "Ronda Mireia Diego 181", numero: 1426, ciudad: "Navarra"}, telefono: "+34 749048649"}
]);

print("✅ Datos insertados correctamente!");
print("Total de registros: " + db.personas.countDocuments());
print("\n📋 Algunos ejemplos:");
db.personas.find().limit(3);