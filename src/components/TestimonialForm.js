import {useContext, useRef, useState} from "react";
import "./TestimonialForm.css";
import {AlertContext} from "../App";
import configData from "../config.json";

export default function TestimonialForm({testimonialAdded}) {
    const nameRef= useRef();
    const ageRef= useRef();
    const locationRef= useRef();
    const imageRef= useRef();
    const commentsRef= useRef();
    const setAlert = useContext(AlertContext);
    const [saving, setSaving] = useState(false);

    const handleSubmit = e => {
        e.preventDefault();
        const testimonial = {
            name: nameRef.current.value,
            age: ageRef.current.value,
            location: locationRef.current.value,
            imageUrl: imageRef.current.value,
            comments: commentsRef.current.value,
        }

        setSaving(true);
        fetch(
            `${configData.SERVER_URL}/testimonials`,
            {
                method: 'POST',
                body: JSON.stringify(testimonial),
                headers: {
                    'Content-Type': 'application/json'
                },
            }
        )
            .then((res) => res.json())
            .then((res) => {
                if (res.error){
                    throw new Error(res.error);
                }
                if (res.message){
                    setAlert({message: res.message});
                }
                testimonialAdded(res);
                e.target.reset();
                e.target.reset();
            })
            .catch((error) => {
                setAlert({error: error.message});
            })
            .finally(() => {
                setSaving(false);

            });
    }

    return (<>
            <h1 className="title">Add Your Voice</h1>
            <form className="testimonial-form" onSubmit={handleSubmit}>
                <input placeholder="Name" ref={nameRef} className="input" required />
                <input placeholder="Age" ref={ageRef} type="number" className="input" required />
                <input placeholder="Location" ref={locationRef} className="input" required />
                <input placeholder="ImageUrl" ref={imageRef} type="url" className="input" required />
                <textarea placeholder="Testimonial" ref={commentsRef} className="input" required />
                <button type="submit" disabled={saving}>Submit</button>
            </form>
        </>
        );
}