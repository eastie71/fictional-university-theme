let btAttributes =  {
        skyColour: {type: "string"},
        grassColour: {type: "string"}
    }

wp.blocks.registerBlockType("craigplugin/are-you-paying-attention", {
    title: "Are You Paying Attention?",
    icon: "smiley",
    category: "common",
    attributes: btAttributes,
    // Edit controls what you see is the Admin post editor screen
    edit: function (props) {
        function updateSkyColour(e) {
            props.setAttributes({skyColour: e.target.value})
        }

        function updateGrassColour(e) {
            props.setAttributes({grassColour: e.target.value})
        }

        return (
            <div>
                <input type="text" placeholder="sky colour" value={props.attributes.skyColour} onChange={updateSkyColour}/>
                <input type="text" placeholder="grass colour" value={props.attributes.grassColour} onChange={updateGrassColour}/>
            </div>
        )
    },
    // Save controls what the actual PUBLIC will see in your content
    save: function (props) {
        return null;
    }
})