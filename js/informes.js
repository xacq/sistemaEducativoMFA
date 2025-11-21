$(document).ready(function () {

    // ===============================
    // CARGAR LISTA DE ESTUDIANTES
    // ===============================
    $("#curso_id").on("change", function () {
        let curso_id = $(this).val();

        if (!curso_id) {
            $("#contenedor_estudiantes").html("");
            return;
        }

        $.post("/ajax/get_estudiantes_by_curso.php", { curso_id }, function (resp) {
            if (resp.status === "ok") {
                let html = "<h3>Estudiantes:</h3>";
                html += "<select id='estudiante_id'>";
                html += "<option value=''>Seleccionar</option>";
                resp.estudiantes.forEach(e => {
                    html += `<option value="${e.id}">${e.nombre}</option>`;
                });
                html += "</select>";
                html += "<button id='btn_generar_estudiante'>Generar Informe</button>";

                $("#contenedor_estudiantes").html(html);
            }
        }, "json");
    });


    // ===============================
    // GENERAR INFORME ESTUDIANTE
    // ===============================
    $(document).on("click", "#btn_generar_estudiante", function () {

        let estudiante_id = $("#estudiante_id").val();
        let curso_id = $("#curso_id").val();

        $.post("/ajax/generar_informe_estudiante.php",
            { estudiante_id, curso_id },
            function (resp) {

                if (resp.status === "ok") {
                    $("#resultado_informe").html(resp.informe);
                } else {
                    alert(resp.message);
                }

            }, "json");
    });


    // ===============================
    // GENERAR INFORME DOCENTE
    // ===============================
    $(document).on("click", "#btn_generar_docente", function () {

        let profesor_id = $("#profesor_id").val();

        $.post("/ajax/generar_informe_docente.php",
            { profesor_id },
            function (resp) {

                if (resp.status === "ok") {
                    $("#resultado_informe").html(resp.informe);
                } else {
                    alert(resp.message);
                }
            }, "json");
    });

});
