
/**
 * @version $Id$
 * @author xjiujiu <xjiujiu@foxmail.com>
 * @description HongJuZi Framework
 * @copyright Copyright (c) 2011-2012 http://www.xjiujiu.com.All right reserved
 */

/**
 * 任务执行工具
 * 
 * 支持任务队列，自动执行，开始、结束、继续、暂停等功能
 * 
 * @author xjiujiu <xjiujiu@foxmail.com>
 * @package hhjslib
 * @since 1.0.0
 */
var HHTask    = function(cfg) {
    this.total          = 0;
    this.times          = 0;
    this.status         = 0;
    this.timer          = null;
    this.restartTimer   = null;
    this.startTime      = 0;
    this.opt            = {
        delay: 4000,
        autoRestartDelay: 3 * 60 * 1000, // 6分钟后重连
        logBoxId: ''
    };
    if('object' === typeof(cfg)) {
        for(var ele in cfg) {
            this.opt[ele]    = cfg[ele];
        }
    }
    this.logTpl  = '<div class="alert alert-block alert-{type}">'
        + ' <button type="button" class="close" data-dismiss="alert"><i class="icon-remove"></i></button>'
        + ' <p><span class="log-time">[{time}]</span>'
        + ' <strong><i class="icon-ok"></i> 【{type}】</strong>'
        + '{message}'
        + ' </p>'
        + ' </div>'
        + ' </div>';
    this.task   = null;
    this.run   = function(url, formData, callback) {
        this.task     = {
            url: url, 
            formData: formData, 
            callback: callback
        };
        this.start();
        this.bindStop();
        this.hold();
        this.comeon();
        this.cleanLog();

        return this;
    };
    this.start   = function() {
        clearInterval(this.restartTimer);
        clearInterval(this.timer);
        this.total  = 0;
        this.times  = 0;
        this.status = 0;
        this._doTask();
        this.info("任务开始执行...", 'write');
    };
    this.openReComeOnJob    = function() {
        this.restartTimer   = setTimeout(function() {
            $("#comeon-btn").click();
        }, this.opt.autoRestartDelay);
    };
    this.bindStop    = function(callback) {
        var _root   = this;
        $("#stop-btn").click(function() {
            _root.stop();
        });

        return this;
    }
    this.stop   = function() {
        clearInterval(this.timer);
        this.info("任务已经结束执行...");
    };
    this.hold   = function() {
        var _root   = this;
        $("#hold-btn").click(function() {
            clearInterval(_root.timer);
            _root.info("任务暂停中...");
        });

        return this;
    };
    this.comeon    = function() {
        var _root   = this;
        $("#comeon-btn").click(function() {
            clearInterval(_root.restartTimer);
            _root._doTask();
            _root.info("任务已经继续...");
        });

        return this;
    };
    this._doTask    = function() {
        var _root   = this;
        _root.timer  = setInterval(function() {
            if(0 === _root.status) {
                _root.status    = 1;
                _root.startTime = new Date().valueOf();
                $.post(
                    _root.task.url,
                    _root.task.formData(),
                    function(response) {
                        var takeTime    = (new Date().valueOf()) - _root.startTime;
                        _root.status  = 0;
                        if(false === response.rs) {
                            _root.warn(response.message);
                            $("#hold-btn").click();
                            _root.openReComeOnJob();
                            return;
                        }
                        _root.times ++;
                        if('undefined' !== typeof(_root.task.callback)) {
                            _root.task.callback(response.data, _root);
                        }
                        _root.info(
                            "Hi，亲刚才安全地完成了第“"
                           + _root.times + "”次任务！用时：" + takeTime + "ms。"
                        );
                    },
                    'json'
                );
            }
        }, this.opt.delay);
    };

    this.info       = function(message, method) {
        this.logging(this.formatMessage(message, 'info'), method);
    };
    this.succeed       = function(message, method) {
        this.logging(this.formatMessage(message, 'success'), method);
    };
    this.warn       = function(message, method) {
        this.logging(this.formatMessage(message, 'warn'), method);
    };
    this.logging    = function(message, method) {
        method      = 'undefined' === typeof(method) ? 'append' : 'html';
        $(this.opt.logBoxId)[method](message);
        var $logBox         = $(this.opt.logBoxId);
        $logBox.scrollTop($logBox[0].scrollHeight);
    };
    this.formatMessage      = function(message, type) {
        return message     = this.logTpl.replace(/{message}/g, message)
        .replace(/{type}/g, type)
        .replace(/{time}/g, (new Date().toLocaleString()))
    };
    this.cleanLog           = function() {
        var _root           = this;
        $("#clean-log-btn").click(function() {
            _root.logging('等待任务执行...', 'write');
        });
    };
};
