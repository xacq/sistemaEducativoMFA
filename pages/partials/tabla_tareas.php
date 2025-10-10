<?php
$tasksToRender = $tasksContext ?? [];
?>
<div class="card mb-4">
    <div class="card-header card-header-academic">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white">Listado de tareas</h5>
            <span class="badge bg-light text-dark"><?php echo count($tasksToRender); ?> registros</span>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($tasksToRender)): ?>
            <p class="text-muted mb-0">No hay tareas registradas en esta categoría.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-academic">
                        <tr>
                            <th>Tarea</th>
                            <th>Curso</th>
                            <th>Fecha de Entrega</th>
                            <th>Estado</th>
                            <th>Última Entrega</th>
                            <th>Calificación</th>
                            <th>Retroalimentación</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasksToRender as $task): ?>
                            <?php
                            $dueDate = !empty($task['fecha_entrega']) ? DateTime::createFromFormat('Y-m-d', $task['fecha_entrega']) : null;
                            $deliveryDate = !empty($task['fecha_envio']) ? DateTime::createFromFormat('Y-m-d H:i:s', $task['fecha_envio']) : null;
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($task['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <?php if (!empty($task['descripcion'])): ?>
                                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($task['descripcion'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($task['curso_nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php if ($dueDate): ?>
                                        <?php echo $dueDate->format('d/m/Y'); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sin fecha</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?php echo htmlspecialchars($task['status_badge'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($task['status_label'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td>
                                    <?php if ($deliveryDate): ?>
                                        <?php echo $deliveryDate->format('d/m/Y H:i'); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sin entrega</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($task['calificacion'] !== null): ?>
                                        <span class="fw-bold"><?php echo number_format((float) $task['calificacion'], 1); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($task['retroalimentacion'])): ?>
                                        <span class="text-muted small"><?php echo nl2br(htmlspecialchars($task['retroalimentacion'], ENT_QUOTES, 'UTF-8')); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">--</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-column gap-2">
                                        <?php if (!empty($task['file_path'])): ?>
                                            <a class="btn btn-sm btn-outline-secondary" href="<?php echo htmlspecialchars($task['file_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                                <i class="bi bi-box-arrow-down"></i> Descargar
                                            </a>
                                        <?php endif; ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-academic"
                                            onclick="openTaskModal(this)"
                                            data-task-id="<?php echo (int) $task['id']; ?>"
                                            data-task-title="<?php echo htmlspecialchars($task['titulo'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-task-course="<?php echo htmlspecialchars($task['curso_nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-task-description="<?php echo htmlspecialchars($task['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-task-comment="<?php echo htmlspecialchars($task['comentario'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        >
                                            <?php echo $task['status'] === 'pending' ? 'Entregar' : 'Actualizar entrega'; ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php unset($tasksContext); ?>
