// Add an URL parser to JQuery that returns an object
// This function is meant to be used with an URL like the window.location
// Simple variable:  ?var=abc                        returns {var: "abc"}
// Simple object:    ?var.length=2&var.scope=123     returns {var: {length: "2", scope: "123"}}
// Simple array:     ?var[]=0&var[]=9                returns {var: ["0", "9"]}
// Array with index: ?var[0]=0&var[1]=9              returns {var: ["0", "9"]}
// Nested objects:   ?my.var.is.here=5               returns {my: {var: {is: {here: "5"}}}}
// All together:     ?var=a&my.var[]=b&my.cookie=no  returns {var: "a", my: {var: ["b"], cookie: "no"}}
// You just cant have an object in an array, e.g. ?var[1].test=abc DOES NOT WORK

function alParseParams(query) {
    var re = /([^&=]+)=?([^&]*)/g;
    var decode = function (str) {
        return decodeURIComponent(str.replace(/\+/g, ' '));
    };

    // recursive function to construct the result object
    function createElement(params, key, value, shouldPush) {
        key = key + '';

        // if the key is a property
        if (key.indexOf('.') !== -1 && (key.indexOf('[') == -1 || (key.indexOf('[') > key.indexOf('.')))) {
            // extract the first part with the name of the object
            var list = key.split('.');

            // the rest of the key
            var new_key = key.split(/\.(.+)?/)[1];

            // create the object if it doesnt exist
            if (!params[list[0]]) params[list[0]] = {};

            // if the key is not empty, create it in the object
            if (new_key !== '') {
                createElement(params[list[0]], new_key, value);
            } else console.warn('parseParams :: empty property in key "' + key + '"', false);
        } else
            // if the key is an array
            if (key.indexOf('[') !== -1) {
                // extract the array name
                var list = key.split('[');
                key = list[0];

                // extract the index of the array
                var list = list[1].split(']');
                var index = list[0]

                // if index is empty, just push the value at the end of the array
                if (index == '') {
                    if (!params) params = {};
                    if (!params[key] || !$.isArray(params[key])) params[key] = [];
                    params[key].push(value);
                } else
                // add the value at the index (must be an integer)
                {
                    if (!params) params = {};
                    if (!params[key] || !$.isArray(params[key])) params[key] = [];

                    if (list[1].indexOf('.') !== -1) {
                        var new_key = list[1].split(/\.(.+)?/)[1];
                        // if the key is not empty, create it in the object
                        if (new_key !== '') {
                            if (!params[key][parseInt(index)] || (!$.isArray(params[key][parseInt(index)]) && !$.isPlainObject(params[key][parseInt(index)]))) {
                                if (new_key.indexOf('.') !== -1 || new_key.indexOf('[') !== -1) {
                                    params[key][parseInt(index)] = [];
                                } else {
                                    params[key][parseInt(index)] = {};
                                }
                            }

                            if (new_key.indexOf('.') !== -1 || new_key.indexOf('[') !== -1)
                                createElement(params[key][parseInt(index)], new_key, value, true);
                            else {
                                if (params[key][parseInt(index)][new_key])
                                    params[key][parseInt(index)][new_key] += ';' + value;
                                else
                                    params[key][parseInt(index)][new_key] = value;
                            }
                        } else console.warn('parseParams :: empty property in key "' + list[1] + '"');
                    } else params[key][parseInt(index)] = value;
                }
            } else
            // just normal key
            {
                if (!params) params = {};
                if (!shouldPush) {
                    params[key] = value;
                } else {
                    var nestedParam = {};
                    params.push(nestedParam);
                    createElement(nestedParam, key, value, false);
                }
            }
    }

    // be sure the query is a string
    query = query + '';

    if (query === '') query = window.location + '';

    var params = {}, e;
    if (query) {
        // remove # from end of query
        if (query.indexOf('#') !== -1) {
            query = query.substr(0, query.indexOf('#'));
        }

        // remove ? at the begining of the query
        if (query.indexOf('?') !== -1) {
            query = query.substr(query.indexOf('?') + 1, query.length);
        }

        // empty parameters
        if (query == '') return {};

        // execute a createElement on every key and value
        while (e = re.exec(query)) {
            var key = decode(e[1]);
            var value = decode(e[2]);
            createElement(params, key, value, false);
        }
    }
    return params;
}

//to be run on engine request start
function requestStart(sender, eventArgs) {
  console.log("requestStart");
  kendo.ui.progress($('#pricingArea'), true);
  if (window.calcStart) {
    calcStart(sender, eventArgs);
  }
  if (window.intCalcStart) {
    intCalcStart(sender, eventArgs);
  }
  $("#detailPage_shippingCalculatorResults").html("");
}
//to be run on engine request end
function responseEnd(sender, eventArgs) {
  console.log("responseEnd");
  kendo.ui.progress($('#pricingArea'), false);
  if (window.calcFinish) {
    calcFinish(sender, eventArgs);
  }
  if (window.intCalcFinish) {
    intCalcFinish(sender, eventArgs);
  }
}

function ResetCalculatorDropdownSelection(pricingParams) {
    if (pricingParams && pricingParams.Options) {
        $(pricingParams.Options).each(function (index) {
            if(pricingParams.Options[index])
            {
                $("#Options\\[" + index + "\\]\\.Value").val(pricingParams.Options[index].Value);
            }
        });
  }
}

function extractNumber(obj, decimalPlaces, allowNegative, separator) {
  "use strict";

  //Assuming '.' as the default separator
  if (!separator)
    separator = '.';

  //will prevent anything other than a number entered withing min and max payment value text boxes
  var temp = obj.value;
  // avoid changing things if already formatted correctly
  var reg0Str = '[0-9]*';
  if (decimalPlaces > 0) {
    reg0Str += '\\' + separator + '?[0-9]{0,' + decimalPlaces + '}';
  } else if (decimalPlaces < 0) {
    reg0Str += '\\' + separator + '?[0-9]*';
  }
  reg0Str = allowNegative ? '^-?' + reg0Str : '^' + reg0Str;
  reg0Str = reg0Str + '$';
  var reg0 = new RegExp(reg0Str);
  if (reg0.test(temp)) return true;

  // first replace all non numbers
  var reg1Str = '[^0-9' + (decimalPlaces != 0 ? separator : '') + (allowNegative ? '-' : '') + ']';
  var reg1 = new RegExp(reg1Str, 'g');
  temp = temp.replace(reg1, '');

  if (allowNegative) {
    // replace extra negative
    var hasNegative = temp.length > 0 && temp.charAt(0) == '-';
    var reg2 = /-/g;
    temp = temp.replace(reg2, '');
    if (hasNegative) temp = '-' + temp;
  }

  if (decimalPlaces != 0) {
    var reg3 = new RegExp('\\' + separator, 'g');
    var reg3Array = reg3.exec(temp);
    if (reg3Array != null) {
      // keep only first occurrence of .
      //  and the number of places specified by decimalPlaces or the entire string if decimalPlaces < 0
      var reg3Right = temp.substring(reg3Array.index + reg3Array[0].length);
      reg3Right = reg3Right.replace(reg3, '');
      reg3Right = decimalPlaces > 0 ? reg3Right.substring(0, decimalPlaces) : reg3Right;
      temp = temp.substring(0, reg3Array.index) + separator + reg3Right;
    }
  }
  obj.value = temp;
}

var pricingAreaQ1 = 0;
var pricingAreaQ2 = 0;
var pricingAreaQ3 = 0;
var pricingAreaQ4 = 0;
var pricingAreaQ5 = 0;
var pricingParameterOld = null;

function getPricingParameters() {
  var isKit = $('#kitArea').length > 0;
  var kitAreaData = isKit ? alParseParams($('#kitArea :input').serialize()) : {};

  var pricingParameters = $('#pricingArea').length > 0 ? alParseParams($('#pricingArea :input').serialize()) : isKit ? { Q1: kitAreaData.txtKitQuantity } : null;

  if (pricingParameters && pricingParameters.SelectedUom && pricingParameters.SelectedUom != '' && $('#Q1').val() == '0')
    $('#Q1').val(1);

  if (pricingParameters)
    pricingParameters.KitParameters = isKit ? kitAreaData.kitParameters : null;

  return pricingParameters;
}

function onEngineLoaded(pricingParametersUsedToLoadEngine) {
  //This is reponsible for formatting the quantity fields on the pricing engine
  $('.pricingGridQuantity').on('keyup',function (event) {
    var decimalPlaces = $(this).data('decimalPlaces');
    var decimalSeparator = $(this).data('decimalSeparator');
    extractNumber($(this)[0], decimalPlaces, false, decimalSeparator);
  });

  $('.pricingGridQuantity, #pricingEngineArea input[type=text]').on('keypress',function (event) {
    if (event.keyCode === 13) {
      calculatePrice();
      event.preventDefault();
    }
  });

  var isKit = $('#kitArea').length > 0;
  var calculatePrice = function () {
    //disable add to cart and customize buttons to prevent pricing error on click before pricing calculated
    // we add data-updating-price flag, so that we can avoid enabled the button when still uploading files 
    $('body').attr('data-updating-price', 'true');
    $("#btnAddToCartButton, #btnCustomizeButton").attr("disabled", "disabled");
    
    //Code to handle field suppressions
    var inputsForSuppression = $('#pricingArea :input');

    for (var input_index = 0; input_index < inputsForSuppression.length; input_index++) {
      var element = inputsForSuppression[input_index];
      var elementType = $(element).attr('type');
      if (elementType && elementType.toLowerCase() == 'hidden')
        continue;

      var suppressionRule = $(element).data('suppression');
      if (suppressionRule && suppressionRule.length > 0) {
        var rules = suppressionRule.split(';');
        for (var rule_index = 0; rule_index < rules.length; rule_index++) {
          var isRuleMatched = false;
          var rule = rules[rule_index];

          var ruleParts = rule.split('=');
          if (ruleParts.length > 1) {
            var source = $('#pricingArea').find("[data-key='" + ruleParts[0] + "']").find(':input');

            for (var src_index = 0; src_index < source.length; src_index++) {
              var src_element = source[src_index];

              var src_elementType = $(src_element).attr('type');
              if (src_elementType && src_elementType.toLowerCase() == 'hidden')
                continue;

              var parentNode = $(element).parent('li');
              if ($(src_element).val() == ruleParts[1]) {
                var defaultValue = $(element).data('default');

                if (defaultValue) {
                  $(element).val(defaultValue);
                }

                parentNode.hide();
                isRuleMatched = true;
                break;
              } else {
                parentNode.show();
              }
            }
          }
          if (isRuleMatched)
            break;
        }
      }
    }

    //Here we run reqiest start, this will eventually run other functions that custom skin developers hook into.
    requestStart(null, null);
    var pricingParameters = getPricingParameters();
    //console.log('get pricing start');
    $.ajax({
      url: "/product/" + $("#urlName").val() + "/pricing",
      type: "POST",
      data: JSON.stringify({ pricingParameters: pricingParameters }),
      dataType: "json",
      contentType: "application/json; charset=utf-8;"
    })
    .done(function (data) {
      //console.log('get pricing end', data);
      if (isKit) {
        if ($("#kitSetTotal").length > 0) //#kitSetTotal is rendered for KitOptionPricingType.EachSelectedKit only
          $("#kitSetTotal").html(data["FormattedCost"]);
        else if ($("#pricingEngine_lblFinalPrice").length > 0) //KitOptionPricingType.SingleProductEngine
          $("#pricingEngine_lblFinalPrice").html(data["FormattedCost"]);

        if (data["ErrorMessage"] && data["ErrorMessage"] != "") {
          $("#KitSetError").html(data["ErrorMessage"]);
          $("#KitSetError").show();
        }
        else {
          $("#KitSetError").html();
          $("#KitSetError").hide();
        }
      } else {
        if (data["ErrorMessage"] && data["ErrorMessage"] != "") {
          $("#priceErrorMessage").html(data["ErrorMessage"]);
          $("#priceErrorMessage").removeClass('hidden');
          $('#Q1').val(pricingAreaQ1);
          $('#Q2').val(pricingAreaQ2);
          $('#Q3').val(pricingAreaQ3);
          $('#Q4').val(pricingAreaQ4);
          $('#Q5').val(pricingAreaQ5);
          //ResetCalculatorDropdownSelection(pricingParameterOld);
          // Revert to previous value will cause issue, as use can not change two option at the same time.
          // So not all combination is available to choose.
          $("#pricingEngine_lblFinalPrice").html('#.##');
          $("#pricingEngine_lblPerPiecePrice").html('#.##');
          window.hasPricingEngineError = true;
        }
        else {
          $("#priceErrorMessage").empty();
          $("#priceErrorMessage").addClass('hidden');
          pricingAreaQ1 = $('#Q1').val();
          pricingAreaQ2 = $('#Q2').val();
          pricingAreaQ3 = $('#Q3').val();
          pricingAreaQ4 = $('#Q4').val();
          pricingAreaQ5 = $('#Q5').val();
          pricingParameterOld = pricingParameters;
          $("#pricingEngine_lblFinalPrice").html(data["FormattedCost"]);
          $("#pricingEngine_lblPerPiecePrice").html(data["FormattedPerUnitCost"]);
          window.hasPricingEngineError = false;
        }
      }
      $("#hdnTotalCost").val(data["Cost"]);
      $("#hdnTotalWeight").val(data["Weight"]);

      //Here we run response end, this will eventually run other functions that custom skin developers hook into.

      // We enable the button if not uploading files
      $('body').attr('data-updating-price', 'false');
      if (parseInt($('body').attr("data-uploading-files") || "0", 10) <= 0) {
          $("#btnAddToCartButton, #btnCustomizeButton").removeAttr("disabled");
      }
      
      responseEnd(null, data);
    })
    .fail(function (error) {
      console.error("Not able to retrieve price. Request failed.", error);
      $("#priceErrorMessage").html("Not able to retrieve price. Request failed.").removeClass('hidden');
      responseEnd(null, error);

    });
  }

  var newPricingParameters = getPricingParameters();
  var usedSamePricingParameters = pricingParametersUsedToLoadEngine && pricingParametersUsedToLoadEngine.length == newPricingParameters.length;
  for (var i = 0; i < newPricingParameters.length; i++) {
    usedSamePricingParameters = usedSamePricingParameters && pricingParametersUsedToLoadEngine && pricingParametersUsedToLoadEngine[i].Key == newPricingParameters[i].Key;
  }

  var supportSuppressions = $('#calcParmInputs').hasClass('supportSuppressions');
  if (!supportSuppressions || usedSamePricingParameters || isKit) {
    var changeCallback = supportSuppressions ? function (e) {
      loadPricingEngine(getPricingParameters());

      //get current active input
      window.calcActiveInputID = e.currentTarget.id;
      //console.log("set active element id: ", window.calcActiveInputID);
    } : function () {
      calculatePrice();
    };

    //if there are control other than textbox and select then need to add event for that
    if ($("#pricingArea :input[type=number]").length) {
      $("#pricingArea :input[type=number]").change(changeCallback);
    }

    if ($("#pricingArea :input[type=text]").length) {
      $("#pricingArea :input[type=text]").on('change',changeCallback);
    }
    
    //remove keypress events added before and make 'enter' key to trigger callback
    $('.pricingGridQuantity, #pricingEngineArea input[type=text]').off("keypress").on("keypress", function (event) {
      if (event.keyCode === 13) {
        $(this).trigger('change');
        event.preventDefault();
      }
    });
      
    if ($("#pricingArea select").length) {
      $("#pricingArea select").on('change',changeCallback);
    }

    if ($("#kitArea :input").length) {
      $("#kitArea :input").on('change',changeCallback);
    }

    $("#pricingArea :input[type=number], #pricingArea :input[type=text], #pricingArea select").on('focus', function (e) {
      window.calcActiveInputID = e.currentTarget.id;
    });

    calculatePrice(); //fire on load
  } else {
    //console.log('used', pricingParametersUsedToLoadEngine ? pricingParametersUsedToLoadEngine.Options : null);
    //console.log('new', newPricingParameters.Options);

    loadPricingEngine(newPricingParameters);
  }
}

function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

function loadPricingEngine(pricingParameters) {

  kendo.ui.progress($('#pricingArea'), true);
  $.ajax({
    url: "/product/" + $("#urlName").val() + "/options/" + $("#orderItemId").val(),
    type: "POST",
    data: JSON.stringify({ pricingParameters: pricingParameters }),
    dataType: "html",
    contentType: "application/json; charset=utf-8;",
  })
  .done(function (result) {
    $("#pricingEngineArea").html(result);
    kendo.ui.progress($('#pricingArea'), false);
    onEngineLoaded(pricingParameters);

    if (!window.calcActiveInputID || window.calcActiveInputID == "undefined") {
      if ($("#pricingArea :input").length) {
        var $firstInput = $('#pricingArea :input').first();
        if(isInViewport($firstInput.get(0))){
          $firstInput.focus();
        }
      }
      //console.log("set focus on first input");
    } else {
      document.getElementById(window.calcActiveInputID).focus();
      //console.log("set focus on active element");
    }

    //console.log("loadPricingEngine done");
  })
  .fail(function (e) {
    console.error("Product/Options AJAX ERROR: ", e);
  });
}

$(document).ready(function () {
  //console.log("document read");
  loadPricingEngine(null);

  $(window).on('unload',function () {
    console.log("unload here");
    window.kendo.ui.progress($('.addToCartButton'), false);
    window.kendo.ui.progress($('#pricingArea'), false);
  });
});
