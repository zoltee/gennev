import { useState, useEffect } from "react";
const useFetch = (url, options, setAlert, done) => {
    const [data, setData] = useState();
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(false);
    options = {...options,
        headers: {
            ...options.headers,
            'Content-Type': 'application/json',
        },
        method: options.method || 'GET',
    }

    useEffect(() => {
        setLoading(true);
        fetch(url, options)
            .then((res) => res.json())
            .then((res) => {
                if (res.error){
                    throw new Error(res.error);
                }
                if (res.message){
                    setAlert({message: res.message});
                }
                setData(res);
                if (done) {
                    done(res);
                }
            })
            .catch((error) => {
                setError(true);
                setAlert({error: error.message});
            })
            .finally(() => {
                setLoading(false);
            });
    }, [options.method, url]);
    return { data, loading, error };
};
export default useFetch;
