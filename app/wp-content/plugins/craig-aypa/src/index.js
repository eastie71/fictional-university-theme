import "./index.scss"
import {TextControl, Flex, FlexBlock, FlexItem, Button, Icon, PanelBody, PanelRow} from "@wordpress/components"
import {InspectorControls, BlockControls, AlignmentToolbar, useBlockProps} from "@wordpress/block-editor"
import {ChromePicker} from "react-color"

// Immediatly Invoked Function Expression - IIFE
(function () {
    let locked = false

    wp.data.subscribe(function() {
        const results = wp.data.select("core/block-editor").getBlocks().filter((block) => {
            return block.name == "craigplugin/are-you-paying-attention" && block.attributes.correctAnswerIndex == undefined
        })
        // Check if we need to disable the Update option in Wordpress - based on if any correct answers are set to "undefined" (no answer selected)
        if (results.length && locked == false) {
           locked = true
           wp.data.dispatch("core/editor").lockPostSaving("nocorrectanswer")
        }
        if (!results.length && locked) {
            locked = false
            wp.data.dispatch("core/editor").unlockPostSaving("nocorrectanswer")
         }
    })
})()

let btAttributes =  {
        question: {type: "string"},
        // Need an empty default here, so that we guarantee to have 1 empty answer
        answers: {type: "array", default: [""]},
        correctAnswerIndex: {type: "number", default: undefined},
        bgColour: {type: "string", default: "#EBEBEB"},
        questionAlignment: {type: "string", default: "left"}
    }

wp.blocks.registerBlockType("craigplugin/are-you-paying-attention", {
    title: "Are You Paying Attention?",
    icon: "smiley",
    category: "common",
    attributes: btAttributes,
    description: "Check your reader's comprehension. Add a multiple choice question.",
    example: {
        attributes: {
            question: "What colour is grass usually?",
            correctAnswerIndex: 3,
            answers: ['Blue', 'Red', 'Green', 'Derek'],
            questionAlignment: "left",
            bgColour: "#CFE8F1"
        }
    },
    // Edit controls what you see is the Admin post editor screen
    edit: EditComponent,
    // Save controls what the actual PUBLIC will see in your content
    save: function (props) {
        return null;
    }
})

function EditComponent(props) {
    const blockProps = useBlockProps({
        className: "paying-attention-edit-block", 
        style: { backgroundColor: props.attributes.bgColour }
    })

    function updateQuestion(value) {
        props.setAttributes({question: value})
    }

    function deleteAnswer(indexToDelete) {
        const newAnswers = props.attributes.answers.concat([])
        newAnswers.splice(indexToDelete, 1)
        if (indexToDelete == props.attributes.correctAnswerIndex) {
            props.setAttributes({correctAnswerIndex: undefined})
        } else if(indexToDelete < props.attributes.correctAnswerIndex) {
            props.setAttributes({correctAnswerIndex: props.attributes.correctAnswerIndex - 1});
        }
        // const newAnswers = props.attributes.answers.filter((x, index) => {
        //     return index != indexToDelete
        // })
        props.setAttributes({answers: newAnswers})
    }

    function markAsCorrectAnswer(correctIndex) {
        props.setAttributes({correctAnswerIndex: correctIndex})
    }

    return (
        <div {...blockProps}>
            <BlockControls>
                <AlignmentToolbar value={props.attributes.questionAlignment} onChange={x => props.setAttributes({questionAlignment: x})} />
            </BlockControls>
            <InspectorControls>
                <PanelBody title="Background Colour" initialOpen={true}>
                    <PanelRow>
                        <ChromePicker color={props.attributes.bgColour} onChangeComplete={ x => props.setAttributes({bgColour: x.hex})} />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <TextControl label="Question:" value={props.attributes.question} onChange={updateQuestion} style={{fontSize: "20px"}}/>
            <p style={{fontSize: "13px", margin: "20px 0 8px 0"}}>Answers:</p>
            {props.attributes.answers.map(function (answer, index) {
                return (
                    <Flex>
                        <FlexBlock>
                            <TextControl value={answer} autoFocus={answer == undefined} onChange={newValue => {
                                {/* concat passing empty array will just return the original answers array */}
                                const newAnswers = props.attributes.answers.concat([])
                                {/* modify the copy of the array setting the newValue for the particular array index */}
                                newAnswers[index] = newValue
                                {/* set the entire answers array */}
                                props.setAttributes({answers: newAnswers})
                            }} />
                        </FlexBlock>
                        <FlexItem>
                            <Button onClick={() => markAsCorrectAnswer(index)}>
                                <Icon className="star-select" icon={props.attributes.correctAnswerIndex == index ? "star-filled" : "star-empty"} />
                            </Button>
                        </FlexItem>
                        <FlexItem>
                            {/* isLink makes the button behave just like an "a" tag link */}
                            <Button isLink className="answer-delete" onClick={() => deleteAnswer(index)}>
                                Delete    
                            </Button>
                        </FlexItem>
                    </Flex>
                )
            })}
            <Button isPrimary onClick={() => {
                props.setAttributes({answers: props.attributes.answers.concat([undefined])})
            }}>Add another answer</Button>
        </div>
    )
}