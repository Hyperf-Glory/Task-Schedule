<html lang="en">
<head>
    <title>Dashboard - Task-Schedule</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/v-charts/lib/style.min.css">
    <style type="text/css">
    /* reset */
    html, body, div, span, dl, dt, dd, ul, ol, li, h1, h2, h3, h4, h5, h6, pre, code, form, fieldset, legend, input, button, textarea, p, blockquote, th, td, button{
    margin:0;
    padding:0;
    border:0;
    outline:0;
    }
    .container{
    padding:60px 40px;
    display:-webkit-flex; /* Safari */
    display:flex;
    flex-direction:row;
    flex-wrap:nowrap;
    }
    .pie{
    width:30%;
    }
    .line{
    width:70%;
    }
    </style>
</head>
<body>
<div id="app">
    <div class="container">
        <div class="pie">
            <ve-pie :data="pieChartData"></ve-pie>
        </div>
        <div class="line">
            <ve-line :data="lineChartData"></ve-line>
        </div>
    </div>
</div>
<script src="https://unpkg.com/vue@2.6.11/dist/vue.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/echarts/dist/echarts.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/v-charts/lib/index.min.js"></script>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
<script>
  new Vue({
    el: '#app',
    data(){
      return {
        timer: null,
        lineChartData: {
          rows: [],
          columns: ["time", "waiting", "reserved", "failed", "delayed", "done"]
        },
        pieChartData: {
          rows: [{
            "status": "waiting",
            "value": 0
          }, {
            "status": "reserved",
            "value": 0
          }, {
            "status": "failed",
            "value": 0
          }, {
            "status": "delayed",
            "value": 0
          }, {
            "status": "done",
            "value": 0
          }],
          columns: ["status", "value"]
        }
      }
    },
    methods: {
      fetch(){
        var _this = this
        axios.get('/api/queue_status')
          .then(function(response){
            if(200 === response.status){
              _this.pieChartData.rows = response.data.data.pie
              _this.lineChartData.rows.push(response.data.data.line)
              if(_this.lineChartData.rows.length > 5000){
                _this.lineChartData.rows.shift()
              }
            }
          })
          .catch(function(error){
            console.error(error)
            clearInterval(_this.timer)
            window.alert('Something wrong, please check the console!')
          });
      }
    },
    created(){
      var _this = this
      this.timer = setInterval(function(){
        _this.fetch()
      }, 1000)
    }
  })
</script>
</body>
</html>
