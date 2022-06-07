import "./index.scss"
import {TextControl, Flex, FlexBlock, FlexItem, Button, Icon} from "@wordpress/components"

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
    edit: EditComponent,
    // Save controls what the actual PUBLIC will see in your content
    save: function (props) {
        return null;
    }
})

function EditComponent(props) {
    function updateSkyColour(e) {
        props.setAttributes({skyColour: e.target.value})
    }

    function updateGrassColour(e) {
        props.setAttributes({grassColour: e.target.value})
    }

    return (
        <div className="paying-attention-edit-block">
            <TextControl label="Question:" style={{fontSize: "20px"}}/>
            <p style={{fontSize: "13px", margin: "20px 0 8px 0"}}>Answers:</p>
            <Flex>
                <FlexBlock>
                    <TextControl />
                </FlexBlock>
                <FlexItem>
                    <Button>
                        <Icon className="star-select" icon="star-empty" />
                    </Button>
                </FlexItem>
                <FlexItem>
                    {/* isLink makes the button behave just like an "a" tag link */}
                    <Button isLink className="answer-delete">
                        Delete    
                    </Button>
                </FlexItem>
            </Flex>
            <Button isPrimary>Add another answer</Button>
        </div>
    )
}