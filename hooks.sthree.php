<?php

class Hooks_sthree extends Hooks 
{

	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Creates JavaScript to add to the Control Panel's footer to handle the action of clearing
	 * the sthree field. Specifically, it removes the HTML elements displaying the current content
	 * and displays the HTML elements for adding new content
	 *
	 * @return string
	 **/
	function control_panel__add_to_foot()
	{
		if (URL::getCurrent() == '/publish') {
			$script = $this->js->inline('
				$(function() {
				  $(".btn-remove-sthree").on("click", function(e) {
				    e.preventDefault();
				    var name = $(this).next("input").attr("name");

				    $(this).parent().siblings(".upload-sthree").removeClass("hidden");
				    
				    $(this).parent().remove();
				  });
				});
			');
			return $script;
		}
	}
}
