<?php
$host = getenv('DB_HOST') ?: 'db';
$dbname = getenv('DB_NAME') ?: 'crud_db';
$user = getenv('DB_USER') ?: 'usuario_web';
$password = getenv('DB_PASSWORD') ?: 'password_seguro';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

$mensaje = "";

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

$productoEditar = null;

if (isset($_GET['editar'])) {
    $id = $_GET['editar'];

    $stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $resultadoEditar = $stmt->get_result();
    $productoEditar = $resultadoEditar->fetch_assoc();
}

$resultado = $conn->query("SELECT * FROM productos ORDER BY id DESC");
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

    <?php if ($mensaje): ?>
        <div class="mensaje">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

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
            </tbody>
        </table>
    </section>

</div>

</body>
</html>
