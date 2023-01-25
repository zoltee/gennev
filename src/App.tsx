import './style.css';
import TestimonialForm from './components/TestimonialForm';
import TestimonialList from "./components/TestimonialList";
import {createContext, useState} from "react";
import useAlert from './hooks/useAlert';
import Alert from "./components/Alert";

export const AlertContext = createContext({});

export default function App(): JSX.Element {
  const { error, message, setAlert } = useAlert();
    const [refreshDate, setRefreshDate] = useState(null);

    const testimonialAdded = () => setRefreshDate((new Date()).getTime())

    return (
    <div className="page">
        <header>Gennev Community Page</header>
        <AlertContext.Provider value={setAlert}>
        <div className="wrapper">
            <Alert error={error} message={message} />

            <div className="main">
                <aside className="form"><TestimonialForm testimonialAdded={testimonialAdded}/></aside>
                <article className="list"><TestimonialList refreshDate={refreshDate} /></article>
            </div>
        </div>
        </AlertContext.Provider>
        <footer>by Zoltan Szalay</footer>
    </div>
  );
}
