import TestimonialGridItem from "./TestimonialGridItem";
import "./TestimonialList.css";
import {useContext, useMemo, useState} from "react";
import Pagination from "./Pagination";
import {AlertContext} from "../App";
import useFetch from "../hooks/useFetch";
import {DebounceInput} from "react-debounce-input";
import classnames from "classnames";
import TestimonialListItem from "./TestimonialListItem";
import configData from "../config.json";

export default function TestimonialList({refreshDate}){
    const [page, setPage] = useState(1);
    const [display, setDisplay] = useState('grid');
    const [search, setSearch] = useState();
    const setAlert = useContext(AlertContext);

    let url = `${configData.SERVER_URL}/testimonials`;
    const queryParams = [];
    if (page) queryParams.push(`page=${page}`);
    if (search) queryParams.push(`search=${search}`);
    if (refreshDate) queryParams.push(`refresh=${refreshDate}`);
    if (queryParams.length) url += '?' + queryParams.join('&');

    const { data, loading } = useFetch(
        url,
        {},
        setAlert
    );

    const items = data?.testimonials || [];
    const pagination = data?.pagination || {};

    const gridItems = useMemo(() => {
        return items.map(item => <li className="item" key={item.id}>
            {display === 'grid' && <TestimonialGridItem item={item}/>}
            {display === 'list' && <TestimonialListItem item={item}/>}
        </li>)
    }, [items, display]);

    return (
        <div className="wrapper">
            <div className="list-header">
                <button className={classnames({selected:display==='grid'})} onClick={() => setDisplay('grid')}>Grid</button>
                <button className={classnames({selected:display==='list'})} onClick={() => setDisplay('list')}>List</button>
                <DebounceInput debounceTimeout={1000} type="text" value={search} onChange={(e) => setSearch(e.target.value)} className="search" placeholder="Search" />
            </div>


            {loading && <h2>Loading...</h2>}
            {!loading &&
                (items?.length > 0 && <ul
                    className={classnames(
                        'testimonial-list',
                        {'display-grid': display==='grid'},
                        {'display-list': display==='list'}
                    )}
                >{gridItems}</ul>)
                ||
                (items.length === 0 && <h2>No Testimonials</h2>)
            }
            {!loading && !!pagination && <Pagination pagination={pagination} setPage={setPage} />}
        </div>
    );
}