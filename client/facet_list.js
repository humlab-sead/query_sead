


function FacetList()
{

    this.facets = Array();    
    this.facets_saved_state = Array();
    
    this.get_facets = function(_volatile)
    {
        return _volatile ? this.facets_saved_state : this.facets;
    };
    
}


/*
* Function: facet_presenter.facet_list.facet_get_facet_by_id
* 
* Gets the facet object given the facet ID.
* 
* Parameters:
* facet_sys_id - The facet system ID.
* volatile - (optional) Whether to get the volative/tmp version of the facet object or not. Defaults to false.
*
* Returns:
* The facet object.
*
* See also:
* <slot_get_slot_by_id>
*/
FacetList.prototype.facet_get_facet_by_id = function(_facet_sys_id, _volatile) {
        
        trace_call_arguments(arguments);
	
	var _objects = this.get_facets(_volatile);
	
	for (var key in _objects) {
            if (_objects[key].id === _facet_sys_id) {
                    return _objects[key];
            }
	}      

	return false;
}
