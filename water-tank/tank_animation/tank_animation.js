FusionCharts.ready(function(){
			var chartObj = new FusionCharts({
    type: 'cylinder',
    dataFormat: 'json',
    renderAt: 'chart-container',
    width: '350',
    height: '370',
    dataSource: {
        "chart": {
            "theme": "fusion",
            "caption": "",
            "subcaption": "",
            "lowerLimit": "0",
            "upperLimit": "800",
            "lowerLimitDisplay": "Empty",
            "upperLimitDisplay": "Full",
            "numberSuffix": " ltrs",
            "showValue": "1",
            "chartBottomMargin": "45",
            "showValue": "0",
			"dataStreamUrl": "read_data2.php",
			"refreshInterval": "1",
			"refreshInstantly": "1",
			"cylFillColor": "#35d1fd",
			"cyloriginx": "125",
			"cyloriginy": "270",
			"cylradius": "120",
			"cylheight": "250"
        },
        "value": "700",
        "annotations": {
            "origw": "400",
            "origh": "290",
            "autoscale": "1",
            "groups": [{
                "id": "range",
                "items": [{
                    "id": "rangeBg",
                    "type": "rectangle",
                    "x": "$canvasCenterX-75",
                    "y": "$chartEndY-40",
                    "tox": "$canvasCenterX +55",
                    "toy": "$chartEndY-80",
                    "fillcolor": "#35d1fd"
                }, {
                    "id": "rangeText",
                    "type": "Text",
                    "fontSize": "20",
                    "fillcolor": "#333333",
                    "text": "Loading...",
                    "x": "$chartCenterX-60",
                    "y": "$chartEndY-60"
                }]
            }]
        }

    },
    "events": {
        "rendered": function(evtObj, argObj) {
           
            evtObj.sender.chartInterval = setInterval(function() {
			    evtObj.sender.feedData && evtObj.sender.feedData("&value=");
            }, 2000);
        },
        /* Using real time update event to update the annotation */
        
        //showing available volume in tank (setting colors as per available volume)
        "realTimeUpdateComplete": function(evt, arg) {
            var annotations = evt.sender.annotations,
                dataVal = evt.sender.getData(),
                colorVal = (dataVal >= 600) ? "#6caa03" : ((dataVal <= 300) ? "#e44b02" : "#f8bd1b");
            //Updating the volume value
            annotations && annotations.update('rangeText', {
                "text": dataVal + " ltrs"
            });
            //setting background color of annotation as per value
            annotations && annotations.update('rangeBg', {
                "fillcolor": colorVal
            });

        },
        "disposed": function(evt, arg) {
            clearInterval(evt.sender.chartInterval);
        }
    }
}
);
			chartObj.render();
		});