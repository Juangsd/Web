<?php

// --- CONFIGURACIÓN DE LA BASE DE DATOS ---
$db_host = "localhost";       // El servidor de la base de datos
$db_user = "bpuma_userMysql21";      // Tu usuario de la base de datos
$db_pass = "sd23ASasDw0Op174";       // Tu contraseña de la base de datos
$db_name = "bpuma_userMysql21";      // El nombre de tu base de datos (generalmente el mismo que el usuario)

// --- INTENTO DE CONEXIÓN ---
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// --- VERIFICAR LA CONEXIÓN ---
if ($conn->connect_error) {
    // Si hay un error, muestra un mensaje y termina el script
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

// Si la conexión fue exitosa, puedes ver este mensaje (para depuración)
echo "¡Conexión a la base de datos establecida correctamente!<br>";

// --- EJEMPLO DE USO DE LA BASE DE DATOS (Opcional, para probar) ---
// Puedes borrar esto si solo quieres la conexión

// 1. Crear una tabla (ejecutar solo UNA VEZ, si no existe la tabla)
// Descomenta las siguientes líneas para crear una tabla simple
/*
$sql_create_table = "CREATE TABLE IF NOT EXISTS mi_tabla (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql_create_table) === TRUE) {
    echo "Tabla 'mi_tabla' creada o ya existe.<br>";
} else {
    echo "Error al crear tabla: " . $conn->error . "<br>";
}
*/

// 2. Insertar un dato (ejecutar para añadir datos)
/*
$nombre_ejemplo = "Ejemplo de Nombre";
$sql_insert = "INSERT INTO mi_tabla (nombre) VALUES ('$nombre_ejemplo')";
if ($conn->query($sql_insert) === TRUE) {
    echo "Nuevo registro insertado correctamente.<br>";
} else {
    echo "Error al insertar registro: " . $conn->error . "<br>";
}
*/

// 3. Seleccionar datos (para ver lo que hay en la tabla)
$sql_select = "SELECT id, nombre, fecha_creacion FROM mi_tabla"; // Asegúrate de que 'mi_tabla' exista
$result = $conn->query($sql_select);

if ($result) { // Verificar si la consulta fue exitosa
    if ($result->num_rows > 0) {
        echo "<h2>Datos de mi_tabla:</h2>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Fecha Creación</th></tr>";
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row["id"] . "</td>";
            echo "<td>" . $row["nombre"] . "</td>";
            echo "<td>" . $row["fecha_creacion"] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "0 resultados encontrados en mi_tabla.<br>";
    }
} else {
    echo "Error en la consulta SELECT: " . $conn->error . "<br>";
}


// --- CERRAR LA CONEXIÓN ---
$conn->close();
echo "Conexión a la base de datos cerrada.<br>";

?>