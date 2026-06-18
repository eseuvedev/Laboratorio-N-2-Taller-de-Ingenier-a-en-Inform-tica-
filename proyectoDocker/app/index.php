<?php
mysqli_report(MYSQLI_REPORT_OFF);

$host = getenv('DB_HOST') ?: 'db';
$dbname = getenv('DB_NAME') ?: 'crud_db';
$user = getenv('DB_USER') ?: 'usuario_web';
$password = getenv('DB_PASSWORD') ?: 'password_seguro';

$conn = null;
$dbDisponible = false;
$errorConexion = "";

/*
    Intentamos conectar varias veces porque, al levantar Docker Compose,
    a veces PHP inicia antes que MariaDB y aparece "Connection refused".
*/
for ($intento = 1; $intento <= 5; $intento++) {
    $conn = @new mysqli($host, $user, $password, $dbname);

    if (!$conn->connect_error) {
        $dbDisponible = true;
        break;
    }

    $errorConexion = $conn->connect_error;
    sleep(2);
}

$mensaje = "";
$productoEditar = null;
$resultado = null;

if ($dbDisponible) {
    $conn->set_charset("utf8mb4");

    /*
        Crea la tabla si no existe.
        Esto ayuda si el volumen de la base de datos se reinicia o si init.sql no se ejecutó.
    */
    $conn->query("
        CREATE TABLE IF NOT EXISTS productos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT,
            precio DECIMAL(10,2) NOT NULL CHECK (precio >= 0),
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    if (isset($_POST['crear'])) {
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $precio = $_POST['precio'];

        if ($precio < 0) {
            $mensaje = "Error: el precio no puede ser negativo.";
        } else {
            $stmt = $conn->prepare("INSERT INTO productos (nombre, descripcion, precio) VALUES (?, ?, ?)");
            $stmt->bind_param("ssd", $nombre, $descripcion, $precio);

            if ($stmt->execute()) {
                $mensaje = "Producto creado correctamente.";
            } else {
                $mensaje = "Error al crear el producto.";
            }
        }
    }

    if (isset($_POST['actualizar'])) {
        $id = $_POST['id'];
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $precio = $_POST['precio'];

        if ($precio < 0) {
            $mensaje = "Error: el precio no puede ser negativo.";
        } else {
            $stmt = $conn->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio = ? WHERE id = ?");
            $stmt->bind_param("ssdi", $nombre, $descripcion, $precio, $id);

            if ($stmt->execute()) {
                $mensaje = "Producto actualizado correctamente.";
            } else {
                $mensaje = "Error al actualizar el producto.";
            }
        }
    }

    if (isset($_GET['eliminar'])) {
        $id = $_GET['eliminar'];

        $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $mensaje = "Producto eliminado correctamente.";
        } else {
            $mensaje = "Error al eliminar el producto.";
        }
    }

    if (isset($_GET['editar'])) {
        $id = $_GET['editar'];

        $stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $resultadoEditar = $stmt->get_result();
        $productoEditar = $resultadoEditar->fetch_assoc();
    }

    $resultado = $conn->query("SELECT * FROM productos ORDER BY id DESC");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Docker PHP + MariaDB</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eef2f7;
            margin: 0;
            padding: 0;
        }

        header {
            background: #1f2937;
            color: white;
            padding: 24px;
            text-align: center;
        }

        header h1 {
            margin: 0;
        }

        header p {
            margin-top: 8px;
            color: #d1d5db;
        }

        nav {
            background: #374151;
            padding: 14px;
            text-align: center;
        }

        nav a {
            color: white;
            margin: 0 14px;
            text-decoration: none;
            font-weight: bold;
        }

        nav a:hover {
            text-decoration: underline;
        }

        .container {
            width: 90%;
            margin: 25px auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        h2 {
            color: #1f2937;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
        }

        form {
            margin-bottom: 30px;
            padding: 18px;
            background: #f9fafb;
            border: 1px solid #d1d5db;
            border-radius: 8px;
        }

        label {
            font-weight: bold;
            color: #374151;
        }

        input, textarea {
            width: 100%;
            padding: 10px;
            margin: 8px 0 16px 0;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            background: #2563eb;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            background: #1d4ed8;
        }

        .cancelar {
            margin-left: 12px;
            color: #6b7280;
            text-decoration: none;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        table th {
            background: #1f2937;
            color: white;
        }

        table th, table td {
            padding: 10px;
            border: 1px solid #d1d5db;
            text-align: left;
        }

        table tr:nth-child(even) {
            background: #f9fafb;
        }

        .mensaje {
            padding: 12px;
            background: #dcfce7;
            border: 1px solid #86efac;
            margin-bottom: 20px;
            border-radius: 5px;
            color: #166534;
            font-weight: bold;
        }

        .error {
            padding: 12px;
            background: #fee2e2;
            border: 1px solid #fca5a5;
            margin-bottom: 20px;
            border-radius: 5px;
            color: #991b1b;
            font-weight: bold;
        }

        .acciones a {
            margin-right: 10px;
            text-decoration: none;
            font-weight: bold;
        }

        .editar {
            color: #2563eb;
        }

        .eliminar {
            color: #dc2626;
        }

        .resumen {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #1e3a8a;
        }

        .integrantes, .crud-info, .mockup {
            margin-bottom: 25px;
            padding: 18px;
            border-radius: 8px;
            background: #f9fafb;
            border: 1px solid #d1d5db;
        }

        .integrantes ul, .crud-info ul {
            margin: 10px 0 0 20px;
            padding: 0;
        }

        .integrantes li, .crud-info li {
            margin-bottom: 8px;
        }

        .mockup img {
            max-width: 100%;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            margin-top: 12px;
        }

        .mockup p {
            color: #4b5563;
        }
    </style>
</head>
<body>

<header>
    <h1>Aplicación Web Dinámica con Docker</h1>
    <p>CRUD con PHP, Apache, MariaDB y Docker Compose</p>
</header>

<nav>
    <a href="#crear">Crear</a>
    <a href="#listar">Leer</a>
    <a href="#listar">Modificar</a>
    <a href="#listar">Borrar</a>
</nav>

<div class="container">

    <div class="resumen">
        Aplicación desplegada en contenedores Docker.
        El servicio web se comunica con una base de datos MariaDB mediante una red virtual Docker y utiliza persistencia mediante volumen.
    </div>

    <section class="integrantes">
        <h2>Integrantes del Proyecto</h2>
        <ul>
            <li>Gabriel Lebien</li>
            <li>Simón Pérez</li>
            <li>Sebastián Valderas</li>
        </ul>
    </section>

    <section class="mockup">
        <h2>Mockup de la Aplicación</h2>
        <p>
            A continuación, se presenta un mockup visual de la aplicación web desarrollada,
            utilizado como referencia para representar la interfaz principal del sistema CRUD.
        </p>
        <img src="mockupIndex.jpeg" alt="Mockup de la aplicación CRUD">
    </section>

    <section class="crud-info">
        <h2>Descripción de Operaciones CRUD</h2>
        <ul>
            <li><strong>Crear:</strong> permite ingresar un nuevo producto mediante un formulario con nombre, descripción y precio.</li>
            <li><strong>Leer:</strong> permite visualizar en una tabla todos los productos almacenados en la base de datos MariaDB.</li>
            <li><strong>Modificar:</strong> permite editar la información de un producto existente y actualizar sus datos.</li>
            <li><strong>Borrar:</strong> permite eliminar un producto registrado en la base de datos.</li>
        </ul>
    </section>

    <?php if (!$dbDisponible): ?>
        <div class="error">
            No se pudo conectar con la base de datos MariaDB.
            Verifica que el contenedor app_db_mariadb esté en ejecución.
            Detalle técnico: <?php echo htmlspecialchars($errorConexion); ?>
        </div>
    <?php endif; ?>

    <?php if ($mensaje): ?>
        <div class="mensaje">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    <?php if ($dbDisponible): ?>

        <section id="crear">
            <h2><?php echo $productoEditar ? "Modificar Producto" : "Crear Producto"; ?></h2>

            <form method="POST">
                <?php if ($productoEditar): ?>
                    <input type="hidden" name="id" value="<?php echo $productoEditar['id']; ?>">
                <?php endif; ?>

                <label>Nombre del producto:</label>
                <input type="text" name="nombre" required
                       value="<?php echo $productoEditar ? htmlspecialchars($productoEditar['nombre']) : ''; ?>">

                <label>Descripción:</label>
                <textarea name="descripcion" required><?php echo $productoEditar ? htmlspecialchars($productoEditar['descripcion']) : ''; ?></textarea>

                <label>Precio:</label>
                <input type="number" name="precio" step="0.01" min="0" required
                       value="<?php echo $productoEditar ? htmlspecialchars($productoEditar['precio']) : ''; ?>">

                <?php if ($productoEditar): ?>
                    <button type="submit" name="actualizar">Actualizar Producto</button>
                    <a class="cancelar" href="index.php">Cancelar</a>
                <?php else: ?>
                    <button type="submit" name="crear">Crear Producto</button>
                <?php endif; ?>
            </form>
        </section>

        <section id="listar">
            <h2>Leer, Modificar y Borrar Productos</h2>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                        <th>Fecha creación</th>
                        <th>Acciones CRUD</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultado && $resultado->num_rows > 0): ?>
                        <?php while ($producto = $resultado->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $producto['id']; ?></td>
                                <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($producto['descripcion']); ?></td>
                                <td>$<?php echo number_format($producto['precio'], 0, ',', '.'); ?></td>
                                <td><?php echo $producto['fecha_creacion']; ?></td>
                                <td class="acciones">
                                    <a class="editar" href="index.php?editar=<?php echo $producto['id']; ?>#crear">Editar</a>
                                    <a class="eliminar" href="index.php?eliminar=<?php echo $producto['id']; ?>"
                                       onclick="return confirm('¿Seguro que deseas eliminar este producto?');">
                                       Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No existen productos registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

    <?php endif; ?>

</div>

</body>
</html>
