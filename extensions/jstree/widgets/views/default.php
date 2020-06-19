<?php
use yii\web\View;

$this->registerJS("$(function(){
    $('#{$this->context->containerId}').jstree({$this->context->clientOptions});

    // $('#search_box').submit(function(e) {
    //     e.preventDefault();
    //     $('#{$this->context->containerId}').jstree(true).search($('#q').val());
    // });

    jsTreeInstance = $('#jstree_container').jstree(true);
selectedNodes = jsTreeInstance.get_selected();
// selectedNodes.forEach(function(id) {
//  nodeValue = jsTreeInstance.get_node(id).data.value;

$('#jstree_container').bind('changed.jstree',
    function (e, data) {
//         alert('Checked: ' + data.node.id);
//         alert('Parent: ' + data.node.parent);
        console.log(JSON.stringify(data.node.id));
    });
});", View::POS_END, "jstree-handler");