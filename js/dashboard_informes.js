$(document).ready(function () {

    // ===============================
    // Cargar estudiantes según curso
    // ===============================
    $("#curso_id").on("change", function () {
        let curso_id = $(this).val();

        if (!curso_id) {
            $("#tabla_estudiantes").html("");
            return;
        }

        $.post("../ajax/get_estudiantes_by_curso.php", { curso_id }, function (resp) {
            if (resp.status === "ok") {

                let html = "<h4>Estudiantes:</h4><table class='table table-bordered'><tr><th>Nombre</th><th>Informe</th></tr>";

                resp.estudiantes.forEach(e => {
                    html += `
                    <tr>
                        <td>${e.nombre}</td>
                        <td><button class='btn btn-primary btn_informe_estudiante' data-id='${e.id}' data-curso='${curso_id}'>
                            Generar Informe
                        </button></td>
                    </tr>`;
                });

                html += "</table>";

                $("#tabla_estudiantes").html(html);
            }
        }, "json");
    });

    // ===============================
    // Generar informe estudiante
    // ===============================
    $(document).on("click", ".btn_informe_estudiante", function () {

        let estudiante_id = $(this).data("id");
        let curso_id = $(this).data("curso");

        $("#panel_informe").html("<b>Generando informe IA...</b>");

        $.post("../ajax/generar_informe_estudiante.php",
            { estudiante_id, curso_id },
            function (resp) {

                if (resp.status === "ok") {
                    $("#panel_informe").html(resp.informe);
                } else {
                    $("#panel_informe").html("<span style='color:red;'>" + resp.message + "</span>");
                }

            }, "json");
    });

    // ===============================
    // Informe docente (solo director)
    // ===============================
    $("#btn_informe_docente").on("click", function () {

        let profesor_id = $("#profesor_id").val();

        if (!profesor_id) {
            alert("Seleccione un profesor");
            return;
        }

        $("#panel_informe").html("<b>Generando informe IA...</b>");

        $.post("../ajax/generar_informe_docente.php",
            { profesor_id },
            function (resp) {

                if (resp.status === "ok") {
                    $("#panel_informe").html(resp.informe);
                } else {
                    $("#panel_informe").html("<span style='color:red;'>" + resp.message + "</span>");
                }

            }, "json");
    });

});
