<main class="container py-5 flex-grow-1">
    
    <!-- ENCABEZADO Y BUSCADOR -->
    <div class="row align-items-center mb-4 pb-2 border-bottom border-dorado-bottom">
        <div class="col-md-6 mb-3 mb-md-0">
            <h2 class="h3 fw-bold text-vino mb-0">
                <i class="bi bi-journal-richtext text-dorado me-2"></i>Artículos Recientes
            </h2>
        </div>
        <div class="col-md-6">
            <!-- Formulario de búsqueda conectado al enrutador (index.php) -->
            <form action="index.php" method="GET" class="d-flex">
                <div class="input-group shadow-sm">
                    <!-- Se mantiene el valor de la búsqueda actual en el placeholder usando htmlspecialchars para seguridad -->
                    <input type="text" name="q" class="form-control border-dorado" placeholder="Buscar temas..." value="<?= htmlspecialchars($termino_busqueda) ?>">
                    <button class="btn btn-vino" type="submit"><i class="bi bi-search"></i> Buscar</button>
                    
                    <!-- Botón para limpiar filtros solo visible si hay una búsqueda o etiqueta activa -->
                    <?php if ($termino_busqueda || $tag_busqueda): ?>
                        <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- CUADRÍCULA DE PUBLICACIONES (GRID) -->
    <div class="row g-4 mb-5">
        <?php if ($resultado && $resultado->num_rows > 0): ?>
            <!-- Bucle para imprimir cada publicación encontrada en la BD -->
            <?php while ($row = $resultado->fetch_assoc()): ?>
                <div class="col-lg-4 col-md-6">
                    <article class="card h-100 card-blog shadow-sm">
                        
                        <!-- RENDERIZADO DE IMAGEN -->
                        <div class="card-img-wrapper">
                            <!-- Validación de existencia física de la imagen para evitar errores 404 en front -->
                            <?php if (!empty($row['imagen']) && file_exists('uploads/' . $row['imagen'])): ?>
                                <img src="uploads/<?= htmlspecialchars($row['imagen']) ?>" class="card-img-custom" alt="Imagen">
                            <?php else: ?>
                                <!-- Placeholder si no hay imagen -->
                                <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-dorado bg-vino-oscuro">
                                    <i class="bi bi-shield-dog fs-1 mb-1"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- CONTENIDO DE LA TARJETA -->
                        <div class="card-body d-flex flex-column p-4">
                            <!-- Título sanitizado -->
                            <h3 class="h5 card-title fw-bold text-vino mb-2"><?= htmlspecialchars($row['titulo']) ?></h3>
                            
                            <!-- Extracto del contenido (Primeros 115 caracteres para uniformidad) -->
                            <p class="card-text text-secondary mb-3 small"><?= htmlspecialchars(substr($row['contenido'], 0, 115)) ?>...</p>
                            
                            <!-- Botón que redirige a la vista completa del artículo pasando el ID -->
                            <a href="publicacion.php?id=<?= $row['id_publicacion'] ?>" class="btn btn-vino btn-sm mt-auto w-100 py-2">
                                Leer artículo
                            </a>
                        </div>

                    </article>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <!-- Estado vacío: cuando la consulta no arroja resultados -->
            <div class="col-12 text-center py-5">
                <h4 class="text-vino fw-bold">No se encontraron artículos</h4>
            </div>
        <?php endif; ?>
    </div>

    <!-- SISTEMA DE PAGINACIÓN -->
    <!-- Solo se muestra si el total de páginas calculado en el controlador es mayor a 1 -->
    <?php if ($total_paginas > 1): ?>
        <nav aria-label="Navegación de páginas">
            <ul class="pagination justify-content-center">
                <!-- Generación de botones numéricos según el total de páginas -->
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?= ($pagina_actual == $i) ? 'active' : '' ?>">
                        <!-- Resaltado dinámico para indicar al usuario en qué página se encuentra -->
                        <a class="page-link <?= ($pagina_actual == $i) ? 'bg-vino border-vino text-white' : 'text-vino' ?>" 
                           href="?pagina=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</main>