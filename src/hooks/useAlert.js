import { useState } from 'react';

export default function useAlert() {
    function setAlert({error, message}) {
        setError(error);
        setMessage(message);
    }

    const [error, setError] = useState();
    const [message, setMessage] = useState();

    return {
        error,
        message,
        setAlert
    }
}
