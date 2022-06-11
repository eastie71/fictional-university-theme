import React, {useState, useEffect} from 'react'
import ReactDOM from 'react-dom'
import "./frontend.scss"

// div with class = "paying-attention-update-me" setup in "index.php"
const divsToUpdate = document.querySelectorAll(".paying-attention-update-me")

divsToUpdate.forEach(function(div) {
    const data = JSON.parse(div.querySelector("pre").innerHTML)
    ReactDOM.render(<Quiz {...data} />, div)
    div.classList.remove("paying-attention-update-me")
})

function Quiz(props) {
    const [isCorrect, setIsCorrect] = useState(undefined)
    const [isCorrectDelayed, setIsCorrectDelayed] = useState(undefined)

    // This is used to reset the isCorrect to undefined - however this relies on the 2600ms magic number
    // Better solution is to use the onAnimationEnd event on the "incorrect-message" div
    // useEffect(() => {
    //     if (isCorrect === false) {
    //         setTimeout(() => {
    //            setIsCorrect(undefined) 
    //         }, 2600)
    //     }
    // }, [isCorrect])
    useEffect(() => {
        if (isCorrect == true) {
            setTimeout(() => {
                setIsCorrectDelayed(true)
            }, 1000)
        }
    }, [isCorrect])

    function handleAnswer(index) {
        if (index == props.correctAnswerIndex) {
            setIsCorrect(true)
        } else {
            setIsCorrect(false)
        }
    }

    return (
        <div className="paying-attention-frontend" style={{backgroundColor: props.bgColour, textAlign: props.questionAlignment}}>
            <p>{props.question}</p>
            <ul>
                {props.answers.map((answer, index) => {
                    return (
                    // Add class "no-click" to disable the click ability on the correct answer (once correct answer is clicked)
                    <li className={(isCorrectDelayed === true && index == props.correctAnswerIndex ? "no-click" : "") +
                                    (isCorrectDelayed === true && index != props.correctAnswerIndex ? "fade-incorrect" : "")} onClick={isCorrect === true ? undefined : () => handleAnswer(index)}>
                        {/* only display this checkmark (tick) - if the delay is true (see useEffect) and the correct answer */}
                        {isCorrectDelayed === true && index == props.correctAnswerIndex && (
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" className="bi bi-check" viewBox="0 0 16 16">
                                <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                            </svg>
                        )}
                        {isCorrectDelayed === true && index != props.correctAnswerIndex && (
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" className="bi bi-x" viewBox="0 0 16 16">
                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                            </svg>
                        )}
                        {answer}
                    </li>)
                })}
            </ul>
            <div className={"correct-message" + (isCorrect == true ? " correct-message--visible" : "")}>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" className="bi bi-emoji-laughing" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M12.331 9.5a1 1 0 0 1 0 1A4.998 4.998 0 0 1 8 13a4.998 4.998 0 0 1-4.33-2.5A1 1 0 0 1 4.535 9h6.93a1 1 0 0 1 .866.5zM7 6.5c0 .828-.448 0-1 0s-1 .828-1 0S5.448 5 6 5s1 .672 1 1.5zm4 0c0 .828-.448 0-1 0s-1 .828-1 0S9.448 5 10 5s1 .672 1 1.5z"/>
                </svg>
                <p>That is correct!</p>
            </div>
            <div onAnimationEnd={() => setIsCorrect(undefined)} className={"incorrect-message" + (isCorrect === false ? " correct-message--visible" : "")}>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" className="bi bi-emoji-frown" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M4.285 12.433a.5.5 0 0 0 .683-.183A3.498 3.498 0 0 1 8 10.5c1.295 0 2.426.703 3.032 1.75a.5.5 0 0 0 .866-.5A4.498 4.498 0 0 0 8 9.5a4.5 4.5 0 0 0-3.898 2.25.5.5 0 0 0 .183.683zM7 6.5C7 7.328 6.552 8 6 8s-1-.672-1-1.5S5.448 5 6 5s1 .672 1 1.5zm4 0c0 .828-.448 1.5-1 1.5s-1-.672-1-1.5S9.448 5 10 5s1 .672 1 1.5z"/>
                </svg>
                <p>Sorry, that is incorrect. Try again!</p>
            </div>
        </div>

    )
}
