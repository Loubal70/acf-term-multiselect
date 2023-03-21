(function($){
    acf.add_filter('select2_args', function(args, element, settings) {
        if (element.parents('.js-multi-taxonomy-select2').length) {
            settings.ajaxAction = 'acf/fields/select_multiple/query';
            args.templateResult = function (data) {
                if (data.id !== null) {
                    let markup = '<div>';
                    if (data.parent) {
                        markup += '<span style="padding-left: 10px;">' + data.text + '</span>';
                    } else {
                        markup += data.text;
                    }
                    markup += '</div>';

                    return markup;
                }
            };
            args.templateSelection = function (data) {
                return data.text;
            };
        }
        return args;
    });
})(jQuery);