// 重写区间拖动条
$.fn.RangeSlider = function(cfg){
    this.sliderCfg = {
        min: cfg && !isNaN(parseFloat(cfg.min)) ? Number(cfg.min) : null,
        max: cfg && !isNaN(parseFloat(cfg.max)) ? Number(cfg.max) : null,
        // step: cfg && Number(cfg.step) ? cfg.step : 1,
        callback: cfg && cfg.callback ? cfg.callback : null
    };

    var $input = $(this);
    var min = this.sliderCfg.min;
    var max = this.sliderCfg.max;
    // var step = this.sliderCfg.step;
    var callback = this.sliderCfg.callback;

    $input.attr('min', min)
        .attr('max', max)
        // .attr('step', step);

    $input.bind("input propertychange", function(e){
        console.log()
        $input.attr('value', this.value);
        let blNum = (this.value - this.min) / (this.max - this.min) * 100
        $input.css( 'background', 'linear-gradient(to right, #2948df, #F1F3F8 ' + blNum + '%, #F1F3F8)' );

        if ($.isFunction(callback)) {
            callback(this);
        }
    });
};