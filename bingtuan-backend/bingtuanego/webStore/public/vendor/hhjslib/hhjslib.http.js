
/**
 * @version $Id$
 * @author xjiujiu <xjiujiu@foxmail.com>
 * @description HongJuZi Framework
 * @copyright Copyright (c) 2011-2012 http://www.xjiujiu.com.All right reserved
 */

/**
 * HTTP请求队列工具类
 * 
 * 这里有对JQuery的get/post的封装，全站使用这一个类进行
 * 异步请求，防止请求的并发，引出的死请求
 * 
 * @author xjiujiu <xjiujiu@foxmail.com>
 * @package None
 * @since 1.0.0
 */
 var HJZHttp    = {
     status: 200,   //空闲状态
     delay: 500,   //2秒执行一次
     timer: null,
     total: 0,
     queue: {},
     init: function() {
         this.startQueue();
     },
     getJSON: function(url, params, callback) {
         this.queue['task-' + this.getTimestamp()]  = {method: 'get', url: url, params: params, callback: callback};
         this.total ++;
     },
     post: function(url, params, callback) {
         this.queue['task-' + this.getTimestamp()]  =  {method: 'post', url: url, params: params, callback: callback};
         this.total ++;
     },
     startQueue: function() {
         var _root      = this;
         this.timer     = setInterval(function() {
             if(200 !== _root.status || 1 > _root.total) {
                 return;
             }
             for(var taskId in _root.queue) {
                _root.status    = 201; //正在请求
                var task   = _root.queue[taskId];
                if('get' === task.method) {
                    _root.getHttp(task.url, task.params, task.callback, taskId);
                    return;
                }
                _root.postHttp(task.url, task.params, task.callback, taskId);
             }
         }, this.delay);
     },
     stopQueue: function() {
         if(null !== this.timer) {
             clearInterval(this.timer);
         }
     },
     getHttp: function(url, params, callback, taskId) {
         var _root  = this;
         $.getJSON(
             url,
             params,
             function(response) {
                 _root.status   = 200;
                 _root.total --;
                 _root.total    = 0 > _root.total ? 0 : _root.total;
                 delete _root.queue[taskId];
                 if(false === response.rs) {
                     return HHJsLib.warn(response.message);
                 }
                 if('undefined' === typeof(callback)) {
                     return;
                 }
                 callback(response);
             }
         );
     },
     postHttp: function(url, params, callback, taskId) {
         var _root  = this;
         $.post(
             url, 
             params, 
             function(response) {
                 _root.status   = 200;
                 _root.total --;
                 _root.total    = 0 > _root.total ? 0 : _root.total;
                 delete _root.queue[taskId]; //删除完成的任务
                 if(false === response.rs) {
                     return HHJsLib.warn(response.message);
                 }
                 if('undefined' === typeof(callback)) {
                     return;
                 }
                 callback(response);
             },
             'json'
         );
     },
     getTimestamp: function() {
         return (new Date()).valueOf();
     },
     hasTask: function(url) {
         for(var ele in this.queue) {
             var task   = this.queue[ele];
             if(url == task.url) {
                 return true;
             }
         }

         return false;
     },
     destroy: function() {
         this.queue     = {};
     }
 };
 //启动Http请求
 HJZHttp.init();
