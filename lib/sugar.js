define(['jquery'],function(j){function s(url){this.url=url;}s.prototype={constructor:s,call:function(args,callback){j.ajax({type:'POST',url:this.url,data:args,success:function(d){var data=JSON.parse(d);callback(data);}});}};return s;});