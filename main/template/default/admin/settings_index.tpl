{% block header %}
{% include "default/layout/main_header.tpl" %}    
{% endblock %}
<script>
$(function() {
    //$("#settings").tabs();
});
</script>
{% block body %}
<div id="settings">      
    <div>
    <div class="span12">
    {% for block_item in blocks %}
        <div id="tabs-{{loop.index}}" class="span5">
            <div class="well_border">
                <h4>{{block_item.icon}} {{block_item.label}}</h4>                
                <div style="list-style-type:none">
                    {{ block_item.search_form }}
                </div>                           
                {% if block_item.items is not null %}
                    <ul>
    		    	{% for url in block_item.items %}
    		    		<li><a href="{{url.url}}">{{ url.label }}</a></li>	    	
    				{% endfor %}
                    </ul>    	
                {% endif %}
                
                {% if block_item.extra is not null %}
                    <div>
                    {{ block_item.extra }}
                    </div>
                {% endif %}                
            </div>
        </div>        
    {% endfor %}
    </div>
    </div>
</div>
{% endblock %}
{% block footer %}
    {#  Footer  #}
    {% if show_footer == true %}
        </div> <!-- end of #row" -->
        </div> <!-- end of #main" -->
        <div class="push"></div>
        </div> <!-- end of #wrapper section -->
    {% endif %}
{% include "default/layout/main_footer.tpl" %}
{% endblock %}