$(function(){
    $(document).on("keydown.autocomplete","#findProjectInput",function(e){
        $(this).autocomplete({
            source: function (request, response) {
                $.ajax({
                    method: "POST",
                    url: window.location.href,
                    data: {action: "autocomplete-project", term: request.term},
                    dataType: "json",
                    success: function (results) {
                        let values = [];
                        results.forEach(function (element) {
                            if (element.key === null || element.key === element.value) {
                                values.push(element);
                            } else {
                                values.push({key: element.key, value: element.key + " | " + element.value});
                            }
                        });
                        response(values);
                    },
                    error: function (msg) {
                        alert(msg.status + " " + msg.responseText);
                    }
                });
            },
            select: function (event, ui) {
                if (ui.item.key !== null) {
                    const value = ui.item.value.split(" | ");
                    $("input[name=\"idproyecto\"]").val(value[0]);
                }
            },
            open: function (event, ui) {
                $(this).autocomplete('widget').css('z-index', 1500);
                return false;
            }
        });
    });

    $('button#deleteProject').on('click', function(){
        $('input[name="idproyecto"]').val('');
        $('input#findProjectInput').val('');
        $(this).parent().html('<span id="searchProject" class="input-group-text"><i class="fa-solid fa-search fa-fw"></i></span>');
    });
});