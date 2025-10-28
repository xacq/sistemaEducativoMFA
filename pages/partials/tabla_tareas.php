<?php if (empty($tasksContext)): ?>
  <div class="alert alert-info">No hay tareas en esta vista.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>Tarea</th>
          <th>Curso</th>
          <th>Fecha límite</th>
          <th>Estado</th>
          <th>Calificación</th>
          <th style="width:220px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($tasksContext as $t): ?>
        <?php
          // Badge de estado
          $badge = '<span class="badge bg-secondary">Pendiente</span>';
          if ($t['estado'] === 'entregada') {
              $badge = '<span class="badge bg-info text-dark">Entregada</span>';
          }
          if ($t['estado'] === 'calificada') {
              $badge = '<span class="badge bg-success">Calificada</span>';
          }

          $califTxt = ($t['estado'] === 'calificada' && $t['calificacion'] !== null)
              ? htmlspecialchars(number_format((float)$t['calificacion'], 2), ENT_QUOTES, 'UTF-8')
              : '—';
        ?>
        <tr>
          <td>
            <strong><?php echo htmlspecialchars($t['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
            <small class="text-muted"><?php echo htmlspecialchars(mb_strimwidth((string)$t['descripcion'], 0, 120, '…'), ENT_QUOTES, 'UTF-8'); ?></small>
          </td>
          <td><?php echo htmlspecialchars($t['curso_nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo $t['fecha_limite'] ? htmlspecialchars($t['fecha_limite'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
          <td><?php echo $badge; ?></td>
          <td><?php echo $califTxt; ?></td>
          <td>
            <?php if ($t['estado'] === 'pendiente'): ?>
              <button
                class="btn btn-sm btn-primary"
                onclick="openTaskModal(this)"
                data-task-id="<?php echo (int)$t['tarea_id']; ?>"
                data-task-title="<?php echo htmlspecialchars($t['titulo'], ENT_QUOTES, 'UTF-8'); ?>"
                data-task-course="<?php echo htmlspecialchars($t['curso_nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                data-task-description="<?php echo htmlspecialchars($t['descripcion'], ENT_QUOTES, 'UTF-8'); ?>"
              >
                Entregar
              </button>

            <?php elseif ($t['estado'] === 'entregada'): ?>
              <button
                class="btn btn-sm btn-outline-primary"
                onclick="openTaskModal(this)"
                data-task-id="<?php echo (int)$t['tarea_id']; ?>"
                data-task-title="<?php echo htmlspecialchars($t['titulo'], ENT_QUOTES, 'UTF-8'); ?>"
                data-task-course="<?php echo htmlspecialchars($t['curso_nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                data-task-description="<?php echo htmlspecialchars($t['descripcion'], ENT_QUOTES, 'UTF-8'); ?>"
              >
                Reemplazar entrega
              </button>
              <?php if (!empty($t['file_path'])): ?>
                <a class="btn btn-sm btn-secondary" href="<?php echo htmlspecialchars($t['file_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Ver archivo</a>
              <?php endif; ?>

            <?php else: /* calificada */ ?>
              <button
                class="btn btn-sm btn-success"
                data-bs-toggle="modal"
                data-bs-target="#gradeModal"
                data-titulo="<?php echo htmlspecialchars($t['titulo'], ENT_QUOTES, 'UTF-8'); ?>"
                data-curso="<?php echo htmlspecialchars($t['curso_nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                data-calificacion="<?php echo htmlspecialchars((string)$t['calificacion'], ENT_QUOTES, 'UTF-8'); ?>"
                data-comentario="<?php echo htmlspecialchars((string)$t['calif_comentario'], ENT_QUOTES, 'UTF-8'); ?>"
                data-profesor="<?php echo htmlspecialchars((string)$t['profesor_nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                data-fecha="<?php echo htmlspecialchars((string)$t['fecha_calificacion'], ENT_QUOTES, 'UTF-8'); ?>"
              >
                Ver calificación
              </button>
              <?php if (!empty($t['file_path'])): ?>
                <a class="btn btn-sm btn-secondary" href="<?php echo htmlspecialchars($t['file_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Ver entrega</a>
              <?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
