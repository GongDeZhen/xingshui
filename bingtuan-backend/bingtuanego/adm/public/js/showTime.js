var days=new  Array ("日", "一", "二", "三", "四", "五", "六");  
function showDT() {  
  var currentDT = new Date();  
  var y,m,date,day,hs,ms,ss,theDateStr;  
  this.y = currentDT.getFullYear(); //四位整数表示的年份  
  this.m = currentDT.getMonth()+1; //月  
  this.date = currentDT.getDate(); //日  
  this.day = currentDT.getDay(); //星期  
  this.hs = currentDT.getHours(); //时  
  this.ms = currentDT.getMinutes(); //分  
  this.ss = currentDT.getSeconds(); //秒  
  var theDateStr = this.y+"年"+  this.m +"月"+this.date+"日 星期"+this.days[this.day]+" "+this.hs+":"+this.ms+":"+this.ss;  
  document.getElementById("theClock").innerHTML = theDateStr;  
  // setTimeout 在执行时,是在载入后延迟指定时间后,去执行一次表达式,仅执行一次  
  window.setTimeout( showDT, 1000);
}