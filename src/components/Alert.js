import "./Alert.css";
import {useState} from "react";

export default function Alert({message, error}){
    const [dismissed, setDismissed] = useState(false);
    if (dismissed) return null;
    return (
    <div className="alert">
        { message && (
            <div className="message">
                {message}
                <i className="dismiss" onClick={()=>setDismissed(true)}>x</i>
            </div>

        )}
        { error && (
            <div className="error">
                {error}
                <i className="dismiss" onClick={()=>setDismissed(true)}>x</i>
            </div>
        )}

    </div>
    )
}
