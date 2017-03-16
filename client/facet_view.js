/*
* File: facet_view
* Class: FacetView
* Module: Facet
* 
* Facet MVP View
* 
* Parameters:
*
* Returns:
*
* See also:
* 
*/

function FacetView()
{
 
}


FacetView.prototype.Render = function(_facet)
{
    new FacetRenderLocator().Locate(_facet.type).Render(_facet);
}




